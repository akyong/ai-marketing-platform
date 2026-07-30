<?php

namespace App\Http\Controllers;

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
        $validated = $request->validate([
            'widget_key' => 'required|string',
            'message' => 'required|string',
            'session_token' => 'nullable|string',
            'visitor_name' => 'nullable|string',
        ]);

        $workspace = Workspace::where('widget_key', $validated['widget_key'])->with('defaultBrand')->first();

        if (! $workspace) {
            $workspace = Workspace::first();
        }

        if (! $workspace) {
            return response()->json([
                'success' => false,
                'reply' => 'Halo! 👋 CS AI Waperu sedang disiapkan. Silakan coba kembali sejenak lagi.',
            ])->header('Access-Control-Allow-Origin', '*');
        }

        $brand = $workspace->brands->first();
        $brandName = $brand ? $brand->name : $workspace->name;

        // Resolve or Create Chat Session
        $sessionToken = $validated['session_token'] ?? (string) Str::uuid();
        $session = CustomerChatSession::firstOrCreate(
            ['session_token' => $sessionToken],
            [
                'workspace_id' => $workspace->id,
                'visitor_name' => $validated['visitor_name'] ?? 'Pengunjung Website',
            ]
        );

        // Record Visitor Message
        CustomerChatMessage::create([
            'session_id' => $session->id,
            'sender' => 'visitor',
            'message' => $validated['message'],
        ]);

        // Construct Customer Support Persona Prompt with Mandatory Bold & Link instructions
        $websiteUrl = config('app.url', 'https://waperu.com');
        $csPrompt = "PERAN: Anda adalah Asisten Customer Support AI Resmi untuk {$brandName} ({$websiteUrl}).\n" .
            "MEMORI BRAND WAPERU:\n" . ($brand?->knowledge_base ?: 'Waperu.com adalah platform toko online mandiri 0% komisi & QRIS COD langsung ke rekening seller.') . "\n\n" .
            "ATURAN FORMAT BALASAN (WAJIB DITURUTI SERIUS):\n" .
            "1. WAJIB sertakan setidaknya satu link website lengkap seperti `{$websiteUrl}` atau `{$websiteUrl}/register` di dalam kalimat balasan agar pengunjung dapat mengeklik link tersebut!\n" .
            "2. WAJIB gunakan format Markdown bold (`**teks tebal**`) untuk menyorot kata/fitur/nama brand penting (contoh: **0% Komisi**, **Fitur QRIS & COD**, **Waperu.com**).\n" .
            "3. Jawab pertanyaan pengunjung berikut dengan ramah, solutif, dan jelas.\n\n" .
            "Pertanyaan Pengunjung: \"{$validated['message']}\"";

        $aiReply = '';
        try {
            $aiReply = $ollama->generate($csPrompt);
        } catch (\Exception $e) {
            $aiReply = '';
        }

        // Fallback to Gemini if Ollama returns empty or error
        if (empty($aiReply) || str_starts_with($aiReply, '⚠️') || str_starts_with($aiReply, '🔑')) {
            $aiReply = $gemini->generate($csPrompt, $brand);
        }

        // Sanitize AI Reply if error prefix returned
        if (empty($aiReply) || str_starts_with($aiReply, '⚠️') || str_starts_with($aiReply, '🔑')) {
            $aiReply = "Halo! 👋 Terima kasih telah menghubungi Customer Support **{$brandName}**.\n\n" .
                "Ada yang bisa saya bantu terkait **toko online mandiri**, **fitur QRIS & COD 0% komisi**, atau solusi jualan tanpa potongan admin di {$websiteUrl}? Silakan tanyakan di sini! 😊";
        }

        // Record AI Reply Message
        CustomerChatMessage::create([
            'session_id' => $session->id,
            'sender' => 'ai',
            'message' => $aiReply,
        ]);

        return response()->json([
            'success' => true,
            'session_token' => $sessionToken,
            'reply' => $aiReply,
            'brand_name' => $brandName,
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
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
