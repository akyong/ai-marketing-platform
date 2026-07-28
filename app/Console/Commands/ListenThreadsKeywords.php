<?php

namespace App\Console\Commands;

use App\Models\AiGeneration;
use App\Models\Workspace;
use App\Services\GeminiService;
use App\Services\ReplizService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListenThreadsKeywords extends Command
{
    protected $signature = 'threads:listen {keyword?}';
    protected $description = 'Riset & Social Listening postingan Threads publik berdasarkan kata kunci (ecommerce, jualanonline, marketplace, komisi marketplace)';

    public function handle(ReplizService $repliz, GeminiService $gemini): int
    {
        $keywords = ['ecommerce', 'jualanonline', 'marketplace', 'komisi marketplace', 'potongan admin'];
        $inputKeyword = $this->argument('keyword');

        if (! empty($inputKeyword)) {
            $keywords = [$inputKeyword];
        }

        $workspace = Workspace::with('brands')->first();
        $brand = $workspace?->brands->first();

        $this->info("🔍 Memulai Threads Social Listening untuk kata kunci: " . implode(', ', $keywords));

        $foundPosts = [];
        foreach ($keywords as $kw) {
            $results = $repliz->searchThreadsContent($kw);
            foreach ($results as $res) {
                $text = $res['text'] ?? $res['description'] ?? $res['content'] ?? '';
                if (! empty($text) && ! in_array($text, $foundPosts)) {
                    $foundPosts[] = $text;
                }
            }
        }

        if (empty($foundPosts)) {
            $this->warn("⚠️ Tidak ditemukan postingan baru di Threads saat ini.");
            return Command::SUCCESS;
        }

        $this->info("💡 Ditemukan " . count($foundPosts) . " postingan Threads relevan!");

        // Distill insights with Gemini AI
        $postsSummary = implode("\n- ", array_map(fn($p) => mb_substr($p, 0, 150), array_slice($foundPosts, 0, 5)));
        $distillPrompt = "Berikut adalah beberapa postingan real-time dari Threads tentang ecommerce/marketplace:\n- {$postsSummary}\n\n" .
            "Ekstrak 1 poin isu/keluhan utama seller yang bisa dijadikan bahan promosi untuk Waperu (misal: keluhan komisi, sulit urus COD, butuh landing page).\n" .
            "Format: 'Riset Threads: [isu utama seller]'";

        $insightRule = $gemini->generate($distillPrompt, $brand);
        $insightRule = trim(str_replace(['**', '###'], '', $insightRule));

        if ($workspace && ! empty($insightRule)) {
            AiGeneration::create([
                'workspace_id' => $workspace->id,
                'type' => 'user_memory_rule',
                'prompt' => $insightRule,
                'response_text' => implode("\n", $foundPosts),
                'status' => 'completed',
                'metadata' => [
                    'source' => 'threads_social_listening',
                    'keywords' => $keywords,
                    'count' => count($foundPosts),
                ],
            ]);

            $this->info("🧠 Insights Baru Berhasil Disimpan Ke Memori AI:");
            $this->line("👉 {$insightRule}");
        }

        return Command::SUCCESS;
    }
}
