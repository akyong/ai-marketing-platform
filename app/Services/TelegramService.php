<?php

namespace App\Services;

use App\Models\ApiKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;

    public function __construct(?string $customToken = null)
    {
        if ($customToken) {
            $this->botToken = $customToken;
        } else {
            $keyRecord = ApiKey::where('provider', 'telegram')->where('is_active', true)->first();
            $this->botToken = (string) ($keyRecord ? $keyRecord->api_key : (config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN', '')));
        }
    }

    /**
     * Send message to a Telegram chat
     */
    public function sendMessage(string|int $chatId, string $text, string $parseMode = 'HTML'): bool
    {
        if (empty($this->botToken)) {
            Log::warning('TELEGRAM_BOT_TOKEN is empty. Cannot send Telegram message.');
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        try {
            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram SendMessage Exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Set Telegram Webhook URL
     */
    public function setWebhook(string $url): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false, 'description' => 'Bot Token Kosong'];
        }

        $endpoint = "https://api.telegram.org/bot{$this->botToken}/setWebhook";
        $response = Http::post($endpoint, ['url' => $url]);

        return $response->json();
    }
}
