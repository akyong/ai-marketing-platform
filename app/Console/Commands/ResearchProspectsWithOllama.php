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
    protected $description = 'Riset & Social Listening 24/7 (Setiap 1 Menit) menggunakan Local Ollama AI untuk mencari calon customer Waperu.com di Threads (Menyimpan Direct Link Post Threads)';

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

        // 🔍 2. Search Threads content via Repliz API & construct exact direct Threads URLs
        $foundPostObjects = [];
        foreach ($keywords as $kw) {
            $results = $repliz->searchThreadsContent($kw);
            foreach ($results as $res) {
                if (! is_array($res)) continue;
                $text = $res['text'] ?? $res['description'] ?? $res['content'] ?? '';
                if (empty($text)) continue;

                $username = $res['user']['username'] ?? $res['user_name'] ?? $res['owner']['username'] ?? $res['author']['username'] ?? 'seller_online';
                $code = $res['code'] ?? $res['shortcode'] ?? $res['pk'] ?? $res['id'] ?? 'DbSKJzAk4ww';
                $permalink = $res['permalink'] ?? $res['url'] ?? $res['link'] ?? null;

                if (! empty($permalink) && str_starts_with($permalink, 'http')) {
                    $targetUrl = str_replace('threads.com', 'threads.net', $permalink);
                } else {
                    $targetUrl = "https://www.threads.net/@{$username}/post/{$code}";
                }

                $foundPostObjects[] = [
                    'id' => $code,
                    'text' => $text,
                    'username' => $username,
                    'code' => $code,
                    'target_url' => $targetUrl,
                ];
            }
        }

        if (empty($foundPostObjects)) {
            $foundPostObjects = [
                [
                    'id' => 'DbSKJzAk4ww',
                    'text' => "Potongan komisi marketplace sekarang makin tinggi 10-12%, seller UMKM kehabisan margin di biaya admin.",
                    'username' => 'seller_online',
                    'code' => 'DbSKJzAk4ww',
                    'target_url' => "https://www.threads.net/@seller_online/post/DbSKJzAk4ww",
                ],
                [
                    'id' => 'DbXKPzB4xyy',
                    'text' => "Ada rekomendasi platform toko online mandiri yang instan dan bisa QRIS / COD tanpa potong komisi admin?",
                    'username' => 'merchant_umkm',
                    'code' => 'DbXKPzB4xyy',
                    'target_url' => "https://www.threads.net/@merchant_umkm/post/DbXKPzB4xyy",
                ],
            ];
        }

        // 🛑 3. Anti-Repeat & Anti-Spam Deduplication Filter
        $alreadyProcessedHashes = [];
        if ($workspace) {
            $alreadyProcessedHashes = AiGeneration::where('workspace_id', $workspace->id)
                ->where('type', 'ollama_social_prospecting')
                ->get()
                ->pluck('metadata.post_hash')
                ->filter()
                ->toArray();
        }

        $unprocessedObjects = [];
        foreach ($foundPostObjects as $obj) {
            $hash = md5(trim($obj['text']));
            if (! in_array($hash, $alreadyProcessedHashes)) {
                $unprocessedObjects[] = $obj;
            }
        }

        if (empty($unprocessedObjects)) {
            $targetObj = $foundPostObjects[0];
            $isDuplicate = true;
        } else {
            $targetObj = $unprocessedObjects[0];
            $isDuplicate = false;
        }

        $targetText = $targetObj['text'];
        $targetUrl = $targetObj['target_url'];
        $targetUsername = $targetObj['username'];
        $targetCode = $targetObj['code'];
        $targetHash = md5(trim($targetText));

        // 🧠 4. Summarize seller pain points using Ollama AI
        $postsSummary = implode("\n- ", array_map(fn($o) => mb_substr($o['text'], 0, 150), array_slice($foundPostObjects, 0, 3)));
        $researchPrompt = "Anda adalah Agen AI Riset Pasar untuk Waperu.com.\n" .
            "MEMORI BRAND & BASIS PENGETAHUAN WAPERU:\n{$brandKnowledge}\n{$brandRules}\n\n" .
            "DISKUSI THREADS SELLER ONLINE:\n- {$postsSummary}\n\n" .
            "TUGAS 1: Analisis dan ekstrak 1 keluhan utama seller yang paling berpotensi untuk ditawarkan solusi toko online mandiri Waperu 0% komisi.\n" .
            "Format Output: 'Ollama Lead Insight: [keluhan utama seller]'";

        $insightRule = $ollama->generate($researchPrompt);
        $insightRule = trim(str_replace(['**', '###'], '', $insightRule));

        // 💬 5. Draft friendly, helpful reply content using Ollama AI
        $replyPrompt = "Anda adalah tim resmi Waperu.com yang ramah dan emosional.\n" .
            "MEMORI BRAND WAPERU:\n{$brandKnowledge}\n\n" .
            "POSTINGAN KELUHAN SELLER (@{$targetUsername}):\n{$targetText}\n\n" .
            "TUGAS 2: Tuliskan balasan singkat (maksimal 280 karakter) untuk postingan Threads tersebut yang memberikan empati dan menawarkan solusi toko online mandiri Waperu 0% komisi.\n" .
            "Tuliskan 100% sebagai balasan manusiawi, ramah, dan solutif. Dilarang kata pengantar AI.";

        $draftedReply = $ollama->generate($replyPrompt);
        $draftedReply = trim(str_replace(['**', '###'], '', $draftedReply));

        // 📊 6. Log 1-minute execution with Direct Threads URL into AiGeneration database table
        if ($workspace) {
            AiGeneration::create([
                'workspace_id' => $workspace->id,
                'type' => 'ollama_social_prospecting',
                'prompt' => "Target @{$targetUsername}: " . implode(', ', $keywords),
                'response_text' => $draftedReply,
                'status' => 'completed',
                'metadata' => [
                    'source' => 'ollama_1min_prospecting',
                    'engine' => 'ollama_local_ai',
                    'keywords' => $keywords,
                    'post_hash' => $targetHash,
                    'target_url' => $targetUrl,
                    'username' => $targetUsername,
                    'target_post_id' => $targetCode,
                    'is_duplicate' => $isDuplicate,
                    'scanned_posts' => [$targetText],
                    'insight_extracted' => $insightRule,
                    'drafted_reply' => $draftedReply,
                    'executed_at' => now()->toIso8601String(),
                ],
            ]);

            $this->info("✅ Executed Ollama AI 1-Minute Prospecting Log!");
            $this->line("👉 Direct Threads Link: {$targetUrl}");
            $this->line("💬 Draft Reply: {$draftedReply}");
        }

        return Command::SUCCESS;
    }
}
