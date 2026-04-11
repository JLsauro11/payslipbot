<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FacebookBotService
{
    private const CACHE_TTL = 3600;
    private const PAYSLIP_DONE_TTL = 1800;

    public function extractSenderId(Request $request): string
    {
        return $request->input('entry.0.messaging.0.sender.id')
            ?? $request->input('entry')[0]['messaging'][0]['sender']['id']
            ?? 'unknown';
    }

    public function verifyAccess(Request $request): void
    {
        if (!$request->isMethod('get')) return;

        $local_token = env('FACEBOOK_MESSENGER_WEBHOOK_TOKEN');
        $hub_verify_token = $request->input('hub_verify_token');

        if ($hub_verify_token === $local_token) {
            exit($request->input('hub_challenge'));
        }
    }

    public function isValidPayload(array $input): bool
    {
        $valid = isset($input['entry'][0]['messaging'][0]);
        Log::info('Payload validation: ' . ($valid ? 'PASS' : 'FAIL'));
        return $valid;
    }

    public function sendMessage(string $recipientId, array $message): void
    {
        $this->sendFacebookMessage([
            'recipient' => ['id' => $recipientId],
            'message' => $message
        ]);
    }

    public function sendQuickReplies(string $recipientId, string $text, array $buttons): void
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

    public function sendButtonTemplate(string $recipientId, string $title, array $buttons): void
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

    public function sendPayslipTemplate(string $senderId, string $pdfUrl): void
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

    // ✅ ALL UI METHODS
    public function showWelcome(string $senderId): void
    {
        Cache::put("bot_state_{$senderId}", ['state' => 'start'], self::CACHE_TTL);
        $this->sendMessage($senderId, ['text' => '🎉 Welcome to RS8 HRD! Get your transactions now!']);
        $this->sendQuickReplies($senderId, 'What transaction would you like?', [
            ['title' => '📄 Get Payslip', 'payload' => 'GET_PAYSLIP'],
            ['title' => '🛡️ Privacy', 'payload' => 'PRIVACY']
        ]);
    }

    public function showPrivacyOptions(string $senderId): void
    {
        Cache::put("bot_state_{$senderId}", ['state' => 'privacy_menu'], self::CACHE_TTL);
        $this->sendQuickReplies($senderId, '🔐 Privacy Options:', [
            ['title' => '🔄 Change Password', 'payload' => 'CHANGE_PASSWORD']
        ]);
    }

    public function askEmployeeId(string $senderId): void
    {
        Cache::put("bot_state_{$senderId}", ['state' => 'waiting_employee_id'], self::CACHE_TTL);
        $this->sendMessage($senderId, ['text' => '🔍 Enter your Employee ID:']);
    }

    public function askPassword(string $senderId, string $employeeName): void
    {
        $this->sendMessage($senderId, ['text' => "🔐 Enter password for {$employeeName}:"]);
    }

    public function askPrivacyEmployeeId(string $senderId): void
    {
        Cache::put("bot_state_{$senderId}", ['state' => 'waiting_privacy_employee_id'], self::CACHE_TTL);
        $this->sendMessage($senderId, ['text' => '👤 Enter your Employee ID:']);
    }

    public function askPrivacyCurrentPassword(string $senderId, string $employeeName): void
    {
        $this->sendMessage($senderId, ['text' => "🔐 Enter current password for {$employeeName}:"]);
    }

    public function askPrivacyNewPassword(string $senderId): void
    {
        $this->sendMessage($senderId, ['text' => '🔑 Enter your NEW password:']);
    }

    public function isEchoMessage(string $senderId): bool
    {
        $stateData = Cache::get("bot_state_{$senderId}", []);
        return isset($stateData['is_echo']) && $stateData['is_echo'];
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
}