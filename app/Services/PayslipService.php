<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PayslipService
{
    private const CACHE_TTL = 3600;
    private const PAYSLIP_DONE_TTL = 1800;

    public function handlePayslipFlow(string $senderId, ?string $text = null, array $stateData = []): bool
    {
        $state = $stateData['state'] ?? 'start';
        Log::info('💰 PAYSLIP FLOW', ['sender' => $senderId, 'state' => $state, 'text' => $text]);

        // ✅ REMOVE STATE GUARD - Controller handles it
        return match($state) {
        'waiting_employee_id' => $this->getEmployeeId($senderId, $text),
        'waiting_password' => $this->verifyPassword($senderId, $text),
        'waiting_payslip_date' => $this->getPayslipDate($senderId, $text),
        'payslip_done' => $this->handlePayslipDone($senderId),
        default => false  // ✅ No 'start' handling here
    };
    }

    public function startPayslipFlow(string $senderId): bool
    {
        app(FacebookBotService::class)->askEmployeeId($senderId);
        return true;
    }

    private function getEmployeeId(string $senderId, ?string $employeeId): bool
    {
        if (!$employeeId) return false;

        $employeeId = trim($employeeId);
        $employee = Employee::where('employee_id', $employeeId)->first();

        if (!$employee) {
            app(FacebookBotService::class)->sendMessage($senderId, ['text' => "❌ Employee ID not found! Please try again."]);
            app(FacebookBotService::class)->askEmployeeId($senderId);
            return true;
        }

        $formattedName = $this->formatEmployeeName($employee);

        Cache::put("bot_state_{$senderId}", [
            'state' => 'waiting_password',
            'employee_id' => $employeeId,
            'employee_name' => $formattedName
        ], self::CACHE_TTL);

        app(FacebookBotService::class)->askPassword($senderId, $formattedName);
        return true;
    }

    private function verifyPassword(string $senderId, ?string $password): bool
    {
        if (!$password) return false;

        $stateData = Cache::get("bot_state_{$senderId}");
        $employeeId = $stateData['employee_id'] ?? null;
        $employeeName = $stateData['employee_name'] ?? null;

        if (!$employeeId) {
            app(FacebookBotService::class)->showWelcome($senderId);
            return true;
        }

        $employee = Employee::where('employee_id', $employeeId)->first();

        if (!$employee || !$employee->verifyPassword($password)) {
            app(FacebookBotService::class)->sendMessage($senderId, ['text' => "❌ Wrong password! Try again."]);
            app(FacebookBotService::class)->askPassword($senderId, $employeeName);
            return true;
        }

        Cache::put("bot_state_{$senderId}", [
            'state' => 'waiting_payslip_date',
            'employee_id' => $employeeId
        ], self::CACHE_TTL);

        app(FacebookBotService::class)->sendMessage($senderId, ['text' => "✅ Password verified!"]);
        $this->askPayslipDate($senderId, $employeeId);
        return true;
    }

    private function askPayslipDate(string $senderId, string $employeeId): void
    {
        $now = Carbon::now();
        $payslipOptions = $this->generatePayslipOptions($now, $employeeId);

        if (empty($payslipOptions)) {
            app(FacebookBotService::class)->sendMessage($senderId, ['text' => '❌ No recent payslips found for this employee.']);
            app(FacebookBotService::class)->sendMessage($senderId, ['text' => '👇 Please try another Employee ID:']);
            Cache::put("bot_state_{$senderId}", ['state' => 'waiting_employee_id'], self::CACHE_TTL);
            return;
        }

        $optionsText = array_map(fn($option) => [
        'title' => $option['display'],
        'payload' => $option['date']
    ], $payslipOptions);

        app(FacebookBotService::class)->sendQuickReplies($senderId, '📅 Select payslip:', $optionsText);
    }

    private function getPayslipDate(string $senderId, ?string $selectedText): bool
    {
        $stateData = Cache::get("bot_state_{$senderId}");
        $employeeId = $stateData['employee_id'] ?? null;

        if (!$employeeId || !$selectedText) {
            app(FacebookBotService::class)->showWelcome($senderId);
            return true;
        }

        $selectedDate = $this->mapSelectedDate($selectedText, $employeeId);
        if (!$selectedDate) {
            app(FacebookBotService::class)->sendMessage($senderId, ['text' => '❌ Invalid selection.']);
            $this->askPayslipDate($senderId, $employeeId);
            return true;
        }

        $this->deliverPayslip($senderId, $employeeId, $selectedDate);
        return true;
    }

    private function deliverPayslip(string $senderId, string $employeeId, string $payslipDate): void
    {
        $payslip = Payslip::where('employee_id', $employeeId)
            ->where('payslip_date', $payslipDate)
            ->first();

        if (!$payslip) {
            app(FacebookBotService::class)->sendMessage($senderId, ['text' => "❌ Payslip not found for {$payslipDate}."]);
            $this->askPayslipDate($senderId, $employeeId);
            return;
        }

        $pdfUrl = "https://pulvinately-unprevented-codi.ngrok-free.dev/payslipbot/payslips/{$payslip->payslip}";
        app(FacebookBotService::class)->sendPayslipTemplate($senderId, $pdfUrl);

        Cache::put("bot_state_{$senderId}", [
            'state' => 'payslip_done',
            'employee_id' => $employeeId
        ], self::PAYSLIP_DONE_TTL);
    }

    private function handlePayslipDone(string $senderId): bool
    {
        app(FacebookBotService::class)->sendMessage($senderId, ['text' => '👆 Type "rs8" for another transaction!']);
        return true;
    }

    // ======================================================
    // ✅ ALL HELPER METHODS - COMPLETE
    // ======================================================

    private function formatEmployeeName(Employee $employee): string
    {
        $firstName = trim($employee->first_name ?? '');
        $lastName = trim($employee->last_name ?? '');
        $middleInitial = trim($employee->middle_initial ?? '');
        $suffix = trim($employee->suffix ?? '');

        $formattedName = $lastName . ', ' . $firstName;

        if ($middleInitial) {
            $formattedName .= ' ' . $middleInitial;
        }

        if ($suffix) {
            $formattedName .= ' ' . $suffix;
        }

        return $formattedName ?: 'Employee';
    }

    private function generatePayslipOptions(Carbon $now, string $employeeId): array
    {
        $options = [];

        $threeMonthsBack = $now->copy()->subMonths(3);
        $twoMonthsBack = $now->copy()->subMonths(2);
        $oneMonthBack = $now->copy()->subMonth();

        $monthsToCheck = [$threeMonthsBack, $twoMonthsBack, $oneMonthBack];

        foreach ($monthsToCheck as $monthDate) {
            $options = array_merge($options, $this->generateMonthOptions($monthDate, $employeeId, $now));
        }

        $uniqueOptions = [];
        foreach ($options as $option) {
            $uniqueOptions[$option['date']] = $option;
        }
        $options = array_values($uniqueOptions);

        $options = array_slice($options, -3);

        usort($options, fn($a, $b) =>
            Carbon::createFromFormat('m/d/Y', $a['date']) <=> Carbon::createFromFormat('m/d/Y', $b['date'])
        );

        return $options;
    }

    private function generateMonthOptions(Carbon $monthDate, string $employeeId, Carbon $now): array
    {
        $options = [];
        $monthYear = $monthDate->year;
        $monthNum = $monthDate->month;
        $monthShort = $monthDate->shortMonthName;

        $date1 = sprintf('%02d/15/%04d', $monthNum, $monthYear);
        if ($this->payslipExists($employeeId, $date1)) {
            $options[] = ['display' => "{$monthShort} 15", 'date' => $date1];
        }

        $day2 = $monthDate->daysInMonth;
        $date2 = sprintf('%02d/%02d/%04d', $monthNum, $day2, $monthYear);

        $includeEndMonth = $monthDate->lt($now) || ($monthDate->equalTo($now) && $now->day > $day2);
        if ($includeEndMonth && $this->payslipExists($employeeId, $date2)) {
            $options[] = ['display' => "{$monthShort} {$day2}", 'date' => $date2];
        }

        return $options;
    }

    private function payslipExists(string $employeeId, string $payslipDate): bool
    {
        return Payslip::where('employee_id', $employeeId)
            ->where('payslip_date', $payslipDate)
            ->exists();
    }

    private function mapSelectedDate(string $selectedText, string $employeeId): ?string
    {
        $now = Carbon::now();
        $options = $this->generatePayslipOptions($now, $employeeId);

        foreach ($options as $option) {
            if ($option['display'] === $selectedText) {
                return $option['date'];
            }
        }

        return preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $selectedText) ? $selectedText : null;
    }
}