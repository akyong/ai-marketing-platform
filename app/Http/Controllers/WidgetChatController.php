<?php

namespace App\Http\Controllers;

use App\Models\AiGeneration;
use App\Models\CustomerChatMessage;
use App\Models\CustomerChatSession;
use App\Models\Workspace;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WidgetChatController extends Controller
{
    /**
     * Handle incoming Customer Support Live Chat message from external websites (e.g. waperu.com)
     */
    public function handleChat(Request $request, GeminiService $gemini, \App\Services\OllamaService $ollama): JsonResponse
    {
        try {
            $messageText = trim($request->input('message', ''));
            $widgetKey = trim($request->input('widget_key', ''));

            if (empty($messageText)) {
                return response()->json([
                    'success' => false,
                    'reply' => 'Silakan tuliskan pertanyaan Anda.',
                ])->header('Access-Control-Allow-Origin', '*');
            }

            $workspace = ! empty($widgetKey)
                ? Workspace::where('widget_key', $widgetKey)->with('defaultBrand')->first()
                : null;

            if (! $workspace) {
                $workspace = Workspace::first();
            }

            $brand = $workspace?->brands->first();
            $brandName = $brand ? $brand->name : ($workspace?->name ?: 'Waperu');

            // Resolve or Create Chat Session
            $sessionToken = $request->input('session_token') ?: (string) Str::uuid();
            $session = null;

            if ($workspace) {
                $session = CustomerChatSession::firstOrCreate(
                    ['session_token' => $sessionToken],
                    [
                        'workspace_id' => $workspace->id,
                        'visitor_name' => $request->input('visitor_name', 'Pengunjung Website'),
                    ]
                );

                CustomerChatMessage::create([
                    'session_id' => $session->id,
                    'sender' => 'visitor',
                    'message' => $messageText,
                ]);
            }

            $brandWebsiteUrl = ! empty($brand?->website_url) ? rtrim($brand->website_url, '/') : 'https://waperu.com';

            // Load Self-Learning Memory Rules & Custom Brand Rules from Database
            $ownerLearnedRules = $workspace ? AiGeneration::where('workspace_id', $workspace->id)
                ->where('type', 'user_memory_rule')
                ->latest()
                ->take(10)
                ->pluck('response_text')
                ->toArray() : [];

            $memoryRulesText = ! empty($ownerLearnedRules)
                ? implode("\n- ", $ownerLearnedRules)
                : 'Gunakan nada ramah, berikan solusi 0% komisi & pendaftaran toko online mandiri.';

            $customRulesText = ! empty($brand?->custom_rules) ? $brand->custom_rules : '-';

            // Construct Customer Support Persona Prompt with Mandatory Bold & Link instructions
            $csPrompt = "PERAN: Anda adalah Asisten Customer Support AI Resmi untuk {$brandName} ({$brandWebsiteUrl}).\n" .
                "BASIS PENGETAHUAN & MEMORI BRAND:\n" . ($brand?->knowledge_base ?: 'Waperu.com adalah platform toko online mandiri 0% komisi & QRIS COD langsung ke rekening seller.') . "\n\n" .
                "🧠 ATURAN MEMORI HASIL PEMBELAJARAN DARI PEMILIK BRAND:\n- {$memoryRulesText}\n\n" .
                "📌 ATURAN KHUSUS BRAND:\n{$customRulesText}\n\n" .
                "ATURAN FORMAT BALASAN (WAJIB DITURUTI):\n" .
                "1. SELALU sertakan link resmi brand `{$brandWebsiteUrl}` atau `{$brandWebsiteUrl}/register` di dalam kalimat balasan (JANGAN PERNAH gunakan gotiau.com!).\n" .
                "2. WAJIB gunakan format Markdown bold (`**teks tebal**`) untuk menyorot kata/fitur/nama brand penting (contoh: **0% Komisi**, **Fitur QRIS & COD**, **Waperu.com**).\n" .
                "3. Jawab pertanyaan pengunjung berikut secara ramah, komunikatif, solutif, dan jelas.\n\n" .
                "Pertanyaan Pengunjung: \"{$messageText}\"";

            $aiReply = '';
            try {
                // Use fast 4s timeout for live chat widget to prevent browser / Nginx 504 timeouts
                $aiReply = $ollama->generate($csPrompt, null, 4);
            } catch (\Throwable $e) {
                $aiReply = '';
            }

            // Fallback to Gemini if Ollama returns empty or error
            if (empty($aiReply) || str_starts_with($aiReply, '⚠️') || str_starts_with($aiReply, '🔑')) {
                try {
                    $aiReply = $gemini->generate($csPrompt, $brand);
                } catch (\Throwable $e) {
                    $aiReply = '';
                }
            }

            // Fallback default message if both AI engines fail
            if (empty($aiReply) || str_starts_with($aiReply, '⚠️') || str_starts_with($aiReply, '🔑')) {
                $aiReply = "Halo! 👋 Terima kasih telah menghubungi Customer Support **{$brandName}**.\n\n" .
                    "Ada yang bisa saya bantu terkait **toko online mandiri**, **fitur QRIS & COD 0% komisi**, atau solusi jualan tanpa potongan admin di {$brandWebsiteUrl}? Silakan tanyakan di sini! 😊";
            }

            // Record AI Reply Message
            if ($session) {
                CustomerChatMessage::create([
                    'session_id' => $session->id,
                    'sender' => 'ai',
                    'message' => $aiReply,
                ]);
            }

            return response()->json([
                'success' => true,
                'session_token' => $sessionToken,
                'reply' => $aiReply,
                'brand_name' => $brandName,
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("WidgetChat Exception: " . $e->getMessage());
            return response()->json([
                'success' => true,
                'reply' => "Halo! 👋 Terima kasih telah menghubungi **Waperu Support**.\n\nSilakan kunjungi website resmi kami di https://waperu.com atau tanyakan seputar pembuatan **toko online 0% komisi**! 😊",
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }
    }

    /**
     * Get chat history for existing session
     */
    public function getHistory(Request $request): JsonResponse
    {
        $sessionToken = $request->query('session_token');
        if (! $sessionToken) {
            return response()->json(['messages' => []])
                ->header('Access-Control-Allow-Origin', '*');
        }

        $session = CustomerChatSession::where('session_token', $sessionToken)->first();
        if (! $session) {
            return response()->json(['messages' => []])
                ->header('Access-Control-Allow-Origin', '*');
        }

        $messages = CustomerChatMessage::where('session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->get(['sender', 'message', 'created_at']);

        return response()->json(['messages' => $messages])
            ->header('Access-Control-Allow-Origin', '*');
    }
}
