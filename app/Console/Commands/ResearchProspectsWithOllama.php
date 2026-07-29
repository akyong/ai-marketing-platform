<?php

namespace App\Console\Commands;

use App\Models\AiGeneration;
use App\Models\Workspace;
use App\Services\OllamaService;
use App\Services\ReplizService;
use Illuminate\Console\Command;

class ResearchProspectsWithOllama extends Command
{
    protected $signature = 'ollama:research {keyword?}';
    protected $description = 'Riset & Social Listening 24/7 menggunakan Local Ollama AI untuk mencari calon customer Waperu.com di Threads';

    public function handle(OllamaService $ollama, ReplizService $repliz): int
    {
        $keywords = ['ecommerce', 'jualanonline', 'marketplace', 'komisi marketplace', 'potongan admin'];
        $inputKeyword = $this->argument('keyword');

        if (! empty($inputKeyword)) {
            $keywords = [$inputKeyword];
        }

        $workspace = Workspace::with('brands')->first();
        $brand = $workspace?->brands->first();

        $this->info("🤖 Memulai 24/7 Ollama Prospect Research untuk kata kunci: " . implode(', ', $keywords));

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

        // Summarize and research prospects using Local Ollama AI
        $postsSummary = implode("\n- ", array_map(fn($p) => mb_substr($p, 0, 150), array_slice($foundPosts, 0, 5)));
        $researchPrompt = "Anda adalah Agen AI Riset Pasar untuk Waperu.com.\n" .
            "Berikut adalah beberapa postingan real-time dari Threads tentang seller online:\n- {$postsSummary}\n\n" .
            "TUGAS: Analisis dan ekstrak 1 keluhan utama seller yang paling berpotensi untuk ditawarkan solusi toko online mandiri Waperu 0% komisi.\n" .
            "Format Output Ringkas: 'Ollama Lead Insight: [keluhan utama seller]'";

        $insightRule = $ollama->generate($researchPrompt);
        $insightRule = trim(str_replace(['**', '###'], '', $insightRule));

        if ($workspace && ! empty($insightRule)) {
            AiGeneration::create([
                'workspace_id' => $workspace->id,
                'type' => 'user_memory_rule',
                'prompt' => $insightRule,
                'response_text' => implode("\n", $foundPosts),
                'status' => 'completed',
                'metadata' => [
                    'source' => 'ollama_prospect_research',
                    'engine' => 'ollama_local_ai',
                    'keywords' => $keywords,
                    'count' => count($foundPosts),
                ],
            ]);

            $this->info("🧠 Prospect Insights Baru dari Local Ollama AI Tersimpan:");
            $this->line("👉 {$insightRule}");
        }

        return Command::SUCCESS;
    }
}
