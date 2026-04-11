<?php

namespace App\Http\Controllers;

use App\Services\FacebookBotService;
use App\Services\PayslipService;
use App\Services\PasswordService;
use App\Models\VerifiedSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FacebookController extends Controller
{
    public function index(Request $request)
    {
        $senderId = app(FacebookBotService::class)->extractSenderId($request);
        Log::info('🔥 WEBHOOK HIT', [
            'sender_id' => $senderId,
            'method' => $request->method(),
            'data' => $request->all()
        ]);

        app(FacebookBotService::class)->verifyAccess($request);

        if (!app(FacebookBotService::class)->isValidPayload($request->all())) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $messaging = $request['entry'][0]['messaging'][0];
        $this->handleMessaging($senderId, $messaging);

        return response()->json(['status' => 'processed'], 200);
    }

    private function handleMessaging(string $senderId, array $messaging): void
    {
        if (isset($messaging['message']['text'])) {
            $this->handleTextMessage($senderId, $messaging['message']['text']);
        } elseif (isset($messaging['postback'])) {
            $this->handlePostback($senderId, $messaging['postback']['payload']);
        }
    }

    private function handleTextMessage(string $senderId, string $text): void
    {
        Log::info('📨 TEXT MESSAGE', ['sender' => $senderId, 'text' => $text]);

        if (app(FacebookBotService::class)->isEchoMessage($senderId)) {
            Log::info('⏭️ ECHO SKIPPED', ['sender' => $senderId]);
            return;
        }

        $cleanText = trim(strtolower($text));
        if ($cleanText === 'rs8') {
            Cache::forget("bot_state_{$senderId}");
            app(FacebookBotService::class)->showWelcome($senderId);
            return;
        }

        $textWithoutEmoji = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $cleanTextNoEmoji = trim(strtolower($textWithoutEmoji));

        // 🚀 1. INITIAL COMMANDS (Quick Replies + Text)
        if (in_array($cleanTextNoEmoji, ['privacy', 'get payslip', 'change password'], true)) {
            Log::info('🚀 INITIAL COMMAND', ['command' => $cleanTextNoEmoji]);

            // ✅ FORCE START FLOWS
            match($cleanTextNoEmoji) {
            'get payslip' => app(PayslipService::class)->startPayslipFlow($senderId),
            'change password' => app(PasswordService::class)->handlePasswordFlow($senderId, null, []),
            'privacy' => app(FacebookBotService::class)->showPrivacyOptions($senderId),
            default => app(FacebookBotService::class)->showWelcome($senderId)
        };
        return;
    }

        // 🚀 2. CONTINUATION FLOWS (State-based)
        $stateData = Cache::get("bot_state_{$senderId}", []);
        if (app(PayslipService::class)->handlePayslipFlow($senderId, $text, $stateData)) return;
        if (app(PasswordService::class)->handlePasswordFlow($senderId, $text, $stateData)) return;

        // 🚀 3. FALLBACK
        app(FacebookBotService::class)->showWelcome($senderId);
    }

    private function handlePostback(string $senderId, string $payload): void
    {
        Log::info('🎛️ POSTBACK', ['sender' => $senderId, 'payload' => $payload]);

        match($payload) {
        'GET_STARTED' => app(FacebookBotService::class)->showWelcome($senderId),
            'GET_PAYSLIP' => app(PayslipService::class)->handlePayslipFlow($senderId, null, ['state' => 'waiting_employee_id']),
            'PRIVACY' => app(FacebookBotService::class)->showPrivacyOptions($senderId),
            'CHANGE_PASSWORD' => app(PasswordService::class)->handlePasswordFlow($senderId, null, ['state' => 'waiting_privacy_employee_id']),
            default => app(FacebookBotService::class)->showWelcome($senderId)
        };
    }
}