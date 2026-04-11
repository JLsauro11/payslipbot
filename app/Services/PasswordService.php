<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PasswordService
{
    private const CACHE_TTL = 3600;

    public function handlePasswordFlow(string $senderId, ?string $text = null, array $stateData = []): bool
    {
        $state = $stateData['state'] ?? 'start';
        Log::info('🔐 PASSWORD FLOW', ['sender' => $senderId, 'state' => $state, 'text' => $text]);

        return match($state) {
        'waiting_privacy_employee_id' => $this->getPrivacyEmployeeId($senderId, $text),
            'waiting_privacy_current_password' => $this->verifyPrivacyCurrentPassword($senderId, $text),
            'waiting_privacy_new_password' => $this->handleNewPassword($senderId, $text),
            'waiting_privacy_confirm_password' => $this->changePassword($senderId, $text),
            default => $this->startPasswordFlow($senderId)
        };
    }

    private function startPasswordFlow(string $senderId): bool
    {
        Log::info('🔐 STARTING PASSWORD FLOW', ['sender' => $senderId]);
        app(FacebookBotService::class)->askPrivacyEmployeeId($senderId);
        return true;
    }

    private function getPrivacyEmployeeId(string $senderId, ?string $employeeId): bool
    {
        if (!$employeeId) return false;

        Log::info('🔐 GET EMPLOYEE ID', ['sender' => $senderId, 'employeeId' => $employeeId]);
        $employeeId = trim($employeeId);
        $employee = Employee::where('employee_id', $employeeId)->first();

        if (!$employee) {
            app(FacebookBotService::class)->sendMessage($senderId, ['text' => "❌ Employee ID not found! Try again."]);
            app(FacebookBotService::class)->askPrivacyEmployeeId($senderId);
            return true;
        }

        $formattedName = $this->formatEmployeeName($employee);

        Cache::put("bot_state_{$senderId}", [
            'state' => 'waiting_privacy_current_password',
            'employee_id' => $employeeId,
            'employee_name' => $formattedName
        ], self::CACHE_TTL);

        app(FacebookBotService::class)->askPrivacyCurrentPassword($senderId, $formattedName);
        return true;
    }

    private function verifyPrivacyCurrentPassword(string $senderId, ?string $currentPassword): bool
    {
        if (!$currentPassword) return false;

        $stateData = Cache::get("bot_state_{$senderId}");
        $employeeId = $stateData['employee_id'] ?? null;
        $employeeName = $stateData['employee_name'] ?? null;

        if (!$employeeId) {
            app(FacebookBotService::class)->showWelcome($senderId);
            return true;
        }

        $employee = Employee::where('employee_id', $employeeId)->first();

        if (!$employee || !$employee->verifyPassword($currentPassword)) {
            app(FacebookBotService::class)->sendMessage($senderId, ['text' => "❌ Wrong current password! Try again."]);
            app(FacebookBotService::class)->askPrivacyCurrentPassword($senderId, $employeeName);
            return true;
        }

        Cache::put("bot_state_{$senderId}", [
            'state' => 'waiting_privacy_new_password',
            'employee_id' => $employeeId
        ], self::CACHE_TTL);

        app(FacebookBotService::class)->sendMessage($senderId, ['text' => "✅ Current password verified!"]);
        app(FacebookBotService::class)->askPrivacyNewPassword($senderId);
        return true;
    }

    private function handleNewPassword(string $senderId, ?string $newPassword): bool
    {
        if (!$newPassword) return false;

        if (strlen($newPassword) < 6) {
            app(FacebookBotService::class)->sendMessage($senderId, ['text' => '❌ Password too short! Min 6 chars.']);
            app(FacebookBotService::class)->askPrivacyNewPassword($senderId);
            return true;
        }

        $stateData = Cache::get("bot_state_{$senderId}", []);
        $employeeId = $stateData['employee_id'] ?? null;

        if (!$employeeId) {
            app(FacebookBotService::class)->showWelcome($senderId);
            return true;
        }

        // ✅ STORE NEW PASSWORD
        Cache::put("bot_state_{$senderId}", [
            'state' => 'waiting_privacy_confirm_password',
            'employee_id' => $employeeId,
            'new_password' => $newPassword
        ], self::CACHE_TTL);

        app(FacebookBotService::class)->sendMessage($senderId, ['text' => '🔄 Confirm new password:']);
        return true;
    }


    private function changePassword(string $senderId, ?string $confirmPassword): bool
    {
        if (!$confirmPassword) return false;

        $stateData = Cache::get("bot_state_{$senderId}");
        $employeeId = $stateData['employee_id'] ?? null;
        $newPassword = $stateData['new_password'] ?? null;

        if (!$employeeId || !$newPassword) {
            app(FacebookBotService::class)->showWelcome($senderId);
            return true;
        }

        if ($newPassword !== $confirmPassword) {
            // ✅ UX IMPROVEMENT: Only repeat CONFIRM (not new password)
            app(FacebookBotService::class)->sendMessage($senderId, [
                'text' => "❌ Passwords don't match!\nPlease confirm '{$newPassword}' exactly:"
            ]);
            // 👇 CHANGE THIS LINE - REPEAT CONFIRM ONLY
            app(FacebookBotService::class)->sendMessage($senderId, ['text' => '🔄 Confirm new password:']);
            return true;
        }

        // ✅ SUCCESS
        $employee = Employee::where('employee_id', $employeeId)->first();
        if (!$employee) {
            app(FacebookBotService::class)->showWelcome($senderId);
            return true;
        }

        $employee->password = $newPassword;
        $employee->save();
        $this->storeVerifiedSender($senderId);

        app(FacebookBotService::class)->sendMessage($senderId, ['text' => '✅ Password changed successfully!']);
        Cache::forget("bot_state_{$senderId}");
        app(FacebookBotService::class)->sendMessage($senderId, ['text' => '✅ Done! Type "rs8" for another transaction.']);
        return true;
    }

    private function storeVerifiedSender(string $senderId): void
    {
        VerifiedSender::firstOrCreate(
            ['sender_id' => $senderId],
            ['sender_id' => $senderId]
        );
        Log::info('✅ VERIFIED SENDER STORED (Password Change)', ['sender_id' => $senderId]);
    }

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
}