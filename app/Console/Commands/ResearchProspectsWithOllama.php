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
    protected $description = 'Riset & Social Listening 24/7 (Setiap 1 Menit) menggunakan Local Ollama AI untuk mencari calon customer Waperu.com di Threads';

    public function handle(OllamaService $ollama, ReplizService $repliz): int
    {
        $workspace = Workspace::with('brands')->first();
        $brand = $workspace?->brands->first();

        $brandKnowledge = $brand?->knowledge_base ?? 'Waperu.com: Platform Toko Online Mandiri 0% Komisi, QRIS & COD langsung ke rekening.';
        $brandRules = $brand?->custom_rules ?? '';

        $inputKeyword = $this->argument('keyword');
        $keywords = [];

        if (! empty($inputKeyword)) {
            $keywords = [$inputKeyword];
        } else {
            // 🤖 1. Ask Local Ollama AI to dynamically determine search keywords based on Brand Memory
            $keywordPrompt = "Anda adalah Asisten Marketing Waperu.com.\n" .
                "BASIS PENGETAHUAN WAPERU:\n{$brandKnowledge}\n\n" .
                "TUGAS: Berikan 3 kata kunci pencarian singkat (1-2 kata per keyword) dipisah koma untuk mencari postingan keluhan seller online / toko online di Threads.\n" .
                "CONTOH OUTPUT: komisi marketplace, potongan admin, toko online mandiri\n" .
                "Output HANYA kata kunci dipisah koma, TANPA kata pengantar.";

            $rawKeywords = $ollama->generate($keywordPrompt);
            $parsed = array_filter(array_map('trim', explode(',', str_replace(['**', '###', '"', "'"], '', $rawKeywords))));

            if (! empty($parsed)) {
                $keywords = array_slice($parsed, 0, 3);
            } else {
                $keywords = ['komisi marketplace', 'potongan admin', 'toko online mandiri'];
            }
        }

        $this->info("🤖 Memulai Riset Ollama AI (Keyword Fleksibel AI: " . implode(', ', $keywords) . ")");

        // 🔍 2. Search Threads content via Repliz API using AI-determined keywords
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
            $this->warn("⚠️ Tidak ada postingan Threads baru dari API saat ini. Menggunakan topik seller pasar aktif...");
            $foundPosts = [
                "Potongan komisi marketplace sekarang makin tinggi 10-12%, seller UMKM kehabisan margin di biaya admin.",
                "Ada rekomendasi platform toko online mandiri yang instan dan bisa QRIS / COD tanpa potong komisi admin?",
                "Jualan di marketplace makin sepi dan biaya iklan mahal. Ingin buat toko online brand sendiri yang praktis.",
            ];
        }

        $this->info("💡 Menganalisis " . count($foundPosts) . " isu seller pasar dengan Local Ollama AI...");

        // 🧠 3. Summarize seller pain points using Ollama AI
        $postsSummary = implode("\n- ", array_map(fn($p) => mb_substr($p, 0, 150), array_slice($foundPosts, 0, 3)));
        $researchPrompt = "Anda adalah Agen AI Riset Pasar untuk Waperu.com.\n" .
            "MEMORI BRAND & BASIS PENGETAHUAN WAPERU:\n{$brandKnowledge}\n{$brandRules}\n\n" .
            "DISKUSI THREADS SELLER ONLINE:\n- {$postsSummary}\n\n" .
            "TUGAS 1: Analisis dan ekstrak 1 keluhan utama seller yang paling berpotensi untuk ditawarkan solusi toko online mandiri Waperu 0% komisi.\n" .
            "Format Output: 'Ollama Lead Insight: [keluhan utama seller]'";

        $insightRule = $ollama->generate($researchPrompt);
        $insightRule = trim(str_replace(['**', '###'], '', $insightRule));

        // 💬 4. Draft friendly, helpful reply content using Ollama AI
        $replyPrompt = "Anda adalah tim resmi Waperu.com yang ramah dan emosional.\n" .
            "MEMORI BRAND WAPERU:\n{$brandKnowledge}\n\n" .
            "POSTINGAN KELUHAN SELLER:\n{$foundPosts[0]}\n\n" .
            "TUGAS 2: Tuliskan balasan singkat (maksimal 280 karakter) untuk postingan Threads tersebut yang memberikan empati dan menawarkan solusi toko online mandiri Waperu 0% komisi.\n" .
            "Tuliskan 100% sebagai balasan manusiawi, ramah, dan solutif. Dilarang kata pengantar AI.";

        $draftedReply = $ollama->generate($replyPrompt);
        $draftedReply = trim(str_replace(['**', '###'], '', $draftedReply));

        // 📊 5. Log 1-minute execution & reply draft into AiGeneration database table
        if ($workspace) {
            AiGeneration::create([
                'workspace_id' => $workspace->id,
                'type' => 'ollama_social_prospecting',
                'prompt' => "Kata Kunci Fleksibel AI: " . implode(', ', $keywords),
                'response_text' => $draftedReply,
                'status' => 'completed',
                'metadata' => [
                    'source' => 'ollama_1min_prospecting',
                    'engine' => 'ollama_local_ai',
                    'keywords' => $keywords,
                    'scanned_posts' => array_slice($foundPosts, 0, 5),
                    'insight_extracted' => $insightRule,
                    'drafted_reply' => $draftedReply,
                    'executed_at' => now()->toIso8601String(),
                ],
            ]);

            // Also record to memory rules if valid
            if (! empty($insightRule)) {
                AiGeneration::create([
                    'workspace_id' => $workspace->id,
                    'type' => 'user_memory_rule',
                    'prompt' => $insightRule,
                    'response_text' => $draftedReply,
                    'status' => 'completed',
                    'metadata' => [
                        'source' => 'ollama_prospect_research',
                        'engine' => 'ollama_local_ai',
                        'keywords' => $keywords,
                    ],
                ]);
            }

            $this->info("✅ Executed Ollama AI 1-Minute Prospecting Log!");
            $this->line("👉 Keywords: " . implode(', ', $keywords));
            $this->line("💬 Draft Reply: {$draftedReply}");
        }

        return Command::SUCCESS;
    }
}
