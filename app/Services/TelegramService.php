<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN', ''));
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
}
