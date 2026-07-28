<?php

namespace App\Http\Controllers;

use App\Models\AiGeneration;
use App\Models\Workspace;
use App\Services\GeminiService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, GeminiService $gemini, TelegramService $telegram): JsonResponse
    {
        $update = $request->all();
        Log::info('Telegram Webhook Update', $update);

        if (! isset($update['message']['text'])) {
            return response()->json(['status' => 'ignored']);
        }

        $chatId = $update['message']['chat']['id'];
        $userText = trim($update['message']['text']);
        $senderName = $update['message']['from']['first_name'] ?? 'User';

        // Get default active Workspace (Waperu)
        $workspace = Workspace::with('defaultBrand')->first();
        $brand = $workspace ? $workspace->brands->first() : null;

        // Process message with Gemini AI
        $aiResponse = $gemini->generate($userText, $brand);

        // Record AI Generation in database
        if ($workspace) {
            AiGeneration::create([
                'workspace_id' => $workspace->id,
                'type' => 'telegram_chat',
                'prompt' => $userText,
                'response_text' => $aiResponse,
                'status' => 'completed',
                'metadata' => [
                    'telegram_chat_id' => $chatId,
                    'sender_name' => $senderName,
                ],
            ]);
        }

        // Format message for Telegram
        $replyText = "🤖 <b>AI Marketing Team ({$brand->name})</b>\n\n" .
            htmlspecialchars($aiResponse);

        // Send reply back to Telegram
        $telegram->sendMessage($chatId, $replyText);

        return response()->json(['status' => 'success']);
    }
}
