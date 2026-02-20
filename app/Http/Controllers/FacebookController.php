<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Payslip;
use App\Models\Employee;
use Carbon\Carbon;
use Exception;

class FacebookController extends Controller // 🔥 FIXED: extends Controller
{
    private const CACHE_TTL = 3600;
    private const PAYSLIP_DONE_TTL = 1800;

    public function index(Request $request)
    {
        $senderId = $this->extractSenderId($request);
        Log::info('🔥 WEBHOOK HIT', [
            'sender_id' => $senderId,
            'method' => $request->method(),
            'data' => $request->all()
        ]);

        $this->verifyAccess($request);

        if (!$this->isValidPayload($request->all())) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $messaging = $request['entry'][0]['messaging'][0];
        $this->handleMessaging($senderId, $messaging);

        return response()->json(['status' => 'processed'], 200);
    }

    private function extractSenderId(Request $request): string
    {
        return $request->input('entry.0.messaging.0.sender.id')
            ?? $request->input('entry')[0]['messaging'][0]['sender']['id']
            ?? 'unknown';
    }

    private function handleMessaging(string $senderId, array $messaging): void
    {
        if (isset($messaging['message']['text'])) {
            $this->handleTextMessage($senderId, $messaging['message']['text']);
        } elseif (isset($messaging['postback'])) {
            $this->handlePostback($senderId, $messaging['postback']['payload']);
        }
    }

    protected function handleTextMessage(string $senderId, string $text): void
    {
        Log::info('📨 TEXT MESSAGE', ['sender' => $senderId, 'text' => $text]);

        if ($this->isEchoMessage($senderId)) {
            Log::info('⏭️ ECHO SKIPPED', ['sender' => $senderId]);
            return;
        }

        $cleanText = trim($text);

        // 🔥 PRIORITY: Reset command
        if (strtolower($cleanText) === 'rs8') {
            Cache::forget("bot_state_{$senderId}");
            $this->showWelcome($senderId);
            return;
        }

        // 🔥 EXACT MATCH: Payslip shortcut
        $cleanText = trim(str_replace('📄 ', '', $text));
        if ($cleanText === 'Get Payslip') {
            $this->askEmployeeId($senderId);
            return;
        }

        $stateData = Cache::get("bot_state_{$senderId}", ['state' => 'start']);
        $state = $stateData['state'];

        Log::info('🔍 STATE DEBUG', ['sender' => $senderId, 'state' => $state]);

        $this->handleState($senderId, $stateData, $cleanText);
    }

    private function handleState(string $senderId, array $stateData, string $text): void
    {
        switch ($stateData['state']) {
            case 'payslip_done':
                $this->handlePayslipDone($senderId);
                break;
            case 'waiting_employee_id':
                $this->getEmployeeId($senderId, $text);
                break;
            case 'waiting_payslip_date':
                $this->getPayslipDate($senderId, $text);
                break;
            default:
                $this->showWelcome($senderId);
                break;
        }
    }

    private function handlePayslipDone(string $senderId): void
    {
        $this->sendMessage($senderId, ['text' => '👆 Type "rs8" for another transaction!']);
    }

    protected function handlePostback(string $senderId, string $payload): void
    {
        Log::info('🎛️ POSTBACK', ['sender' => $senderId, 'payload' => $payload]);

        switch ($payload) {  // 🔥 Changed from match to switch
            case 'GET_STARTED':
                $this->showWelcome($senderId);
                break;
            case 'GET_PAYSLIP':
                $this->askEmployeeId($senderId);
                break;
            default:
                $this->showWelcome($senderId);
                break;
        }
    }


    // 🔥 STATE METHODS (unchanged logic, better organization)
    protected function showWelcome(string $senderId): void
    {
        Cache::put("bot_state_{$senderId}", ['state' => 'start'], self::CACHE_TTL);
        $this->sendMessage($senderId, ['text' => '🎉 Welcome to RS8 HRD! Get your transactions now!']);
        $this->sendQuickReplies($senderId, 'What transaction would you like?', [
            ['title' => '📄 Get Payslip', 'payload' => 'GET_PAYSLIP']
        ]);
    }

    protected function askEmployeeId(string $senderId): void
    {
        Cache::put("bot_state_{$senderId}", ['state' => 'waiting_employee_id'], self::CACHE_TTL);
        $this->sendMessage($senderId, ['text' => '🔍 Enter your Employee ID:']);
    }

    protected function getEmployeeId(string $senderId, string $employeeId): void
    {
        $employeeId = trim($employeeId);
        $employee = Employee::where('employee_id', $employeeId)->first();

        if (!$employee) {
            $this->sendMessage($senderId, ['text' => "❌ Employee ID not found! Please try again."]);
            $this->askEmployeeId($senderId);
            return;
        }

        Cache::put("bot_state_{$senderId}", [
            'state' => 'waiting_payslip_date',
            'employee_id' => $employeeId
        ], self::CACHE_TTL);

        $this->sendMessage($senderId, ['text' => "✅ Found employee: {$employee->name}"]);
        $this->askPayslipDate($senderId, $employeeId);
    }

    protected function askPayslipDate(string $senderId, string $employeeId): void
    {
        $now = Carbon::now();
        $payslipOptions = $this->generatePayslipOptions($now, $employeeId);

        if (empty($payslipOptions)) {
            $this->sendMessage($senderId, ['text' => '❌ No recent payslips found for this employee.']);
            $this->sendMessage($senderId, ['text' => '👇 Please try another Employee ID:']);
            Cache::put("bot_state_{$senderId}", ['state' => 'waiting_employee_id'], self::CACHE_TTL);
            return;
        }

        $optionsText = array_map(fn($option) => [
        'title' => $option['display'],
        'payload' => $option['date']
    ], $payslipOptions);

        $this->sendQuickReplies($senderId, '📅 Select payslip:', $optionsText);
    }

    // 🔥 DATABASE METHODS (unchanged, well-structured)
    protected function generatePayslipOptions(Carbon $now, string $employeeId): array
    {
        $options = [];
        $monthsToCheck = [
            $now->copy()->subMonths(2),
            $now->copy()->subMonth(),
            ...($now->day >= 15 ? [$now->copy()] : [])
        ];

        foreach ($monthsToCheck as $monthDate) {
            $options = array_merge($options, $this->generateMonthOptions($monthDate, $employeeId, $now));
        }

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

        // 15th cutoff
        $date1 = sprintf('%02d/15/%04d', $monthNum, $monthYear);
        if ($this->payslipExists($employeeId, $date1)) {
            $options[] = ['display' => "{$monthShort} 15", 'date' => $date1];
        }

        // End of month
        $day2 = $monthDate->daysInMonth;
        $date2 = sprintf('%02d/%02d/%04d', $monthNum, $day2, $monthYear);

        $includeEndMonth = $monthDate->lt($now) || ($monthDate->equalTo($now) && $now->day > $day2);
        if ($includeEndMonth && $this->payslipExists($employeeId, $date2)) {
            $options[] = ['display' => "{$monthShort} {$day2}", 'date' => $date2];
        }

        return $options;
    }

    // 🔥 MESSAGING METHODS (Laravel HTTP client)
    protected function sendMessage(string $recipientId, array $message): void
    {
        $this->sendFacebookMessage([
            'recipient' => ['id' => $recipientId],
            'message' => $message
        ]);
    }

    protected function sendQuickReplies(string $recipientId, string $text, array $buttons): void
    {
        $this->sendFacebookMessage([
            'recipient' => ['id' => $recipientId],
            'message' => [
                'text' => $text,
                'quick_replies' => collect($buttons)->map(fn($btn) => [
        'content_type' => 'text',
        'title' => $btn['title'],
        'payload' => $btn['payload']
    ])->all()
            ]
        ]);
    }

    protected function sendButtonTemplate(string $recipientId, string $title, array $buttons): void
    {
        $this->sendFacebookMessage([
            'recipient' => ['id' => $recipientId],
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => $title,
                        'buttons' => $buttons
                    ]
                ]
            ]
        ]);
    }

    private function sendFacebookMessage(array $response): void
    {
        $url = 'https://graph.facebook.com/v21.0/me/messages?access_token=' . env('PAGE_ACCESS_TOKEN');

        $result = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $response);

        Log::info('Facebook API Response', [
            'http_code' => $result->status(),
            'response' => $result->body()
        ]);
    }

    // 🔥 UTILITY METHODS
    private function isEchoMessage(string $senderId): bool
    {
        $stateData = Cache::get("bot_state_{$senderId}", []);
        return isset($stateData['is_echo']) && $stateData['is_echo'];
    }

    protected function payslipExists(string $employeeId, string $payslipDate): bool
    {
        return Payslip::where('employee_id', $employeeId)
            ->where('payslip_date', $payslipDate)
            ->exists();
    }

    protected function getPayslipDate(string $senderId, string $selectedText): void
    {
        $stateData = Cache::get("bot_state_{$senderId}");
        $employeeId = $stateData['employee_id'] ?? null;

        if (!$employeeId) {
            $this->showWelcome($senderId);
            return;
        }

        $selectedDate = $this->mapSelectedDate($selectedText, $employeeId);
        if (!$selectedDate) {
            $this->sendMessage($senderId, ['text' => '❌ Invalid selection.']);
            $this->askPayslipDate($senderId, $employeeId);
            return;
        }

        $this->deliverPayslip($senderId, $employeeId, $selectedDate);
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

    private function deliverPayslip(string $senderId, string $employeeId, string $payslipDate): void
    {
        $payslip = Payslip::where('employee_id', $employeeId)
            ->where('payslip_date', $payslipDate)
            ->first();

        if (!$payslip) {
            $this->sendMessage($senderId, ['text' => "❌ Payslip not found for {$payslipDate}."]);
            $this->askPayslipDate($senderId, $employeeId);
            return;
        }

        $pdfUrl = config('app.url') . "/payslipbot/payslips/{$payslip->payslip}"; // 🔥 CONFIG-BASED URL
        $this->sendPayslipTemplate($senderId, $pdfUrl);

        Cache::put("bot_state_{$senderId}", [
            'state' => 'payslip_done',
            'employee_id' => $employeeId
        ], self::PAYSLIP_DONE_TTL);
    }

    private function sendPayslipTemplate(string $senderId, string $pdfUrl): void
    {
        $this->sendMessage($senderId, ['text' => '📄 Payslip ready!']);
        $this->sendMessage($senderId, ['text' => '👆 Tap below to download:']);
        $this->sendButtonTemplate($senderId, '📥 Download Payslip', [[
            'type' => 'web_url',
            'title' => 'View Payslip PDF',
            'url' => $pdfUrl,
            'webview_height_ratio' => 'tall'
        ]]);
        $this->sendMessage($senderId, ['text' => '✅ Done! Type "rs8" for another transaction.']);
    }

    // 🔥 VALIDATION & VERIFICATION
    private function isValidPayload(array $input): bool
    {
        $valid = isset($input['entry'][0]['messaging'][0]);
        Log::info('Payload validation: ' . ($valid ? 'PASS' : 'FAIL'));
        return $valid;
    }

    private function verifyAccess(Request $request): void
    {
        if (!$request->isMethod('get')) {
            return;
        }

        $local_token = env('FACEBOOK_MESSENGER_WEBHOOK_TOKEN');
        $hub_verify_token = $request->input('hub_verify_token');

        if ($hub_verify_token === $local_token) {
            exit($request->input('hub_challenge'));
        }
    }
}
