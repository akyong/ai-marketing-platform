<?php

namespace App\Http\Controllers;

use App\Models\AiGeneration;
use App\Models\ApiKey;
use App\Models\Post;
use App\Models\Workspace;
use App\Services\AiAgentCoordinator;
use App\Services\GeminiService;
use App\Services\ReplizService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, GeminiService $gemini, TelegramService $telegram, AiAgentCoordinator $coordinator): JsonResponse
    {
        $update = $request->all();
        Log::info('Telegram Webhook Update', $update);

        if (! isset($update['message']['text'])) {
            return response()->json(['status' => 'ignored']);
        }

        $chatId = $update['message']['chat']['id'];
        $userText = trim($update['message']['text']);
        $senderName = $update['message']['from']['first_name'] ?? 'Boss';

        // Get default active Workspace (Waperu)
        $workspace = Workspace::with('defaultBrand')->first();
        $brand = $workspace ? $workspace->brands->first() : null;
        $brandName = $brand ? $brand->name : 'Waperu';

        // Handle Slash Commands
        if (str_starts_with($userText, '/')) {
            $command = strtolower(explode(' ', $userText)[0]);

            switch ($command) {
                case '/start':
                case '/help':
                    $reply = "🤖 <b>AI Marketer Telegram Controller ({$brandName})</b>\n\n" .
                        "Halo {$senderName}! Anda bisa mengontrol & memberi masukan langsung ke AI Marketer dari Telegram:\n\n" .
                        "📌 <b>Perintah Tersedia:</b>\n" .
                        "• /postnow — 🚀 <b>Test Instant Post Ke Repliz Sekarang!</b>\n" .
                        "• /generate — Jalankan 3-step riset & buat campaign baru\n" .
                        "• /stats — Cek status & statistik marketing hari ini\n" .
                        "• /memory — 🧠 Lihat daftar memori/catatan masukan yang diingat AI\n" .
                        "• /clearmemory — Hapus seluruh memori catatan AI\n\n" .
                        "💡 <i>Kirim pesan biasa (misal: 'buat postingan lebih santai dan sebut bosku') untuk memberikan masukan yang langsung diingat permanen oleh AI!</i>";
                    $telegram->sendMessage($chatId, $reply);
                    return response()->json(['status' => 'command_processed']);

                case '/stats':
                case '/status':
                    $genCount = AiGeneration::where('workspace_id', $workspace?->id)->count();
                    $scheduledCount = Post::where('workspace_id', $workspace?->id)->where('status', 'scheduled')->count();
                    $publishedCount = Post::where('workspace_id', $workspace?->id)->where('status', 'published')->count();
                    $activeKeys = ApiKey::where('is_active', true)->count();
                    $memoryCount = AiGeneration::where('workspace_id', $workspace?->id)->where('type', 'user_memory_rule')->count();

                    $reply = "📊 <b>Statistik AI Marketer ({$brandName}) Hari Ini</b>\n\n" .
                        "• 🧠 Total Eksekusi AI: <b>{$genCount}</b>\n" .
                        "• 🧠 Memori Masukan Tersimpan: <b>{$memoryCount} Catatan</b>\n" .
                        "• ⏳ Postingan Terjadwal: <b>{$scheduledCount}</b>\n" .
                        "• 🚀 Postingan Published (Repliz): <b>{$publishedCount}</b>\n" .
                        "• 🔑 Active API Keys Pool: <b>{$activeKeys}</b>\n\n" .
                        "Status Autopilot: 🟢 <b>AKTIF 24/7</b>";
                    $telegram->sendMessage($chatId, $reply);
                    return response()->json(['status' => 'command_processed']);

                case '/memory':
                case '/memori':
                case '/rules':
                    $memoryRules = AiGeneration::where('workspace_id', $workspace?->id)
                        ->where('type', 'user_memory_rule')
                        ->latest()
                        ->take(10)
                        ->pluck('prompt')
                        ->map(fn($r, $i) => ($i + 1) . ". " . $r)
                        ->implode("\n");

                    if (empty($memoryRules)) {
                        $reply = "🧠 <b>Memori AI Kosong</b>\n\nBelum ada masukan khusus dari Anda. Kirim pesan biasa (misal: <i>'buat postingan lebih santai dan sebut bosku'</i>) untuk mencatat masukan baru yang diingat permanen oleh AI!";
                    } else {
                        $reply = "🧠 <b>Daftar Catatan & Masukan Tersimpan ({$brandName}):</b>\n\n" .
                            htmlspecialchars($memoryRules) . "\n\n" .
                            "💡 <i>Ketik /clearmemory jika ingin menghapus seluruh memori.</i>";
                    }
                    $telegram->sendMessage($chatId, $reply);
                    return response()->json(['status' => 'command_processed']);

                case '/clearmemory':
                    AiGeneration::where('workspace_id', $workspace?->id)
                        ->where('type', 'user_memory_rule')
                        ->delete();
                    $telegram->sendMessage($chatId, "🧹 <b>Seluruh memori & catatan khusus AI telah dibersihkan!</b>");
                    return response()->json(['status' => 'command_processed']);

                case '/generate':
                    $telegram->sendMessage($chatId, "⚡ <i>AI Marketer sedang meriset pasar & menyusun campaign 3-step... Mohon tunggu sebentar.</i>");

                    $result = $coordinator->runAutonomousCampaign(
                        $workspace,
                        "Buat strategi marketing harian untuk menarik seller online dan UMKM ke {$brandName}"
                    );

                    $reply = "✅ <b>Autonomous Campaign Berhasil Dihasilkan!</b>\n\n" .
                        "📌 <b>Hasil Copywriting & Jadwal:</b>\n\n" .
                        mb_substr($result['copywriting'], 0, 1000) . "...\n\n" .
                        "<i>Postingan telah dijadwalkan dan akan diterbitkan otomatis via Repliz!</i>";
                    $telegram->sendMessage($chatId, $reply);
                    return response()->json(['status' => 'command_processed']);

                case '/postnow':
                case '/instant':
                case '/publishnow':
                    $telegram->sendMessage($chatId, "🚀 <i>AI Marketer v3 (Self-Learning + Social Listening) sedang meriset pasar & menerbitkan POSTINGAN INSTAN ke Repliz...</i>");

                    $campaignResult = $coordinator->runAutonomousCampaign(
                        $workspace,
                        "Buat postingan promosi instan untuk {$brandName} yang menjawab keluhan seller online saat ini"
                    );

                    // Fetch the latest created scheduled post to publish immediately
                    $latestPost = Post::where('workspace_id', $workspace?->id)
                        ->latest()
                        ->first();

                    if ($latestPost) {
                        $repliz = new ReplizService();
                        $success = $repliz->publish($latestPost);

                        if ($success) {
                            $reply = "🚀 <b>BERHASIL POST INSTAN KE REPLIZ!</b>\n\n" .
                                "📌 <b>Judul:</b> {$latestPost->title}\n" .
                                "🌐 <b>Platform Target:</b> Facebook, Instagram, Threads, TikTok\n" .
                                "🆔 <b>Repliz Post ID:</b> {$latestPost->repliz_post_id}\n\n" .
                                "<b>Preview Copywriting:</b>\n" .
                                htmlspecialchars(mb_substr($latestPost->content, 0, 800)) . "...";
                        } else {
                            $reply = "❌ <b>GAGAL DIPUBLIKASIKAN KE REPLIZ</b>\n\n" .
                                "Log Error: " . htmlspecialchars($latestPost->error_message ?? 'Kunci API Repliz belum aktif atau gagal autentikasi.');
                        }
                    } else {
                        $reply = "❌ <b>GAGAL: Postingan tidak berhasil dibuat.</b>";
                    }

                    $telegram->sendMessage($chatId, $reply);
                    return response()->json(['status' => 'command_processed']);
            }
        }

        // Process custom text / feedback from Telegram user and save to AI Memory Engine
        $distillPrompt = "Pengguna memberikan masukan/perintah berikut untuk strategi marketing brand {$brandName}:\n\"{$userText}\"\n\n" .
            "Tuliskan 1 kalimat instruksi/aturan singkat (dalam Bahasa Indonesia) yang wajib ditaati AI untuk postingan-postingan berikutnya. " .
            "Format: 'Catatan Pengguna: [aturan]'";

        $distilledRule = $gemini->generate($distillPrompt, $brand);

        // Clean distilled rule
        $distilledRule = trim(str_replace(['**', '###'], '', $distilledRule));

        // Store memory rule in database
        if ($workspace) {
            AiGeneration::create([
                'workspace_id' => $workspace->id,
                'type' => 'user_memory_rule',
                'prompt' => $distilledRule,
                'response_text' => $userText,
                'status' => 'completed',
                'metadata' => [
                    'telegram_chat_id' => $chatId,
                    'sender_name' => $senderName,
                    'original_message' => $userText,
                ],
            ]);
        }

        // Format confirmation message for Telegram
        $replyText = "🧠 <b>Masukan & Catatan Disimpan Ke Memori AI!</b>\n\n" .
            "📌 <b>Aturan Memori Baru:</b>\n<i>" . htmlspecialchars($distilledRule) . "</i>\n\n" .
            "💡 <i>Seluruh kampanye & postingan AI berikutnya (termasuk yang dijadwalkan & via /postnow) otomatis mematuhi catatan ini!</i>\n\n" .
            "Ketik /memory untuk melihat seluruh memori tersimpan.";

        $telegram->sendMessage($chatId, $replyText);

        return response()->json(['status' => 'success']);
    }
}
