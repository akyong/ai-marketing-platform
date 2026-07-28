<?php

namespace App\Services;

use App\Models\AiGeneration;
use App\Models\Brand;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;

class AiAgentCoordinator
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Run Autonomous Self-Learning Marketing Workflow with Threads Social Listening
     */
    public function runAutonomousCampaign(Workspace $workspace, string $goal = 'Buat strategi marketing harian untuk menarik seller online ke Waperu'): array
    {
        $brand = $workspace->brands->first();
        $brandName = $brand->name ?? 'Waperu';

        // 🧠 Step 0: Recall Past Post Memory to avoid repeating angles
        $pastPostsSummary = Post::where('workspace_id', $workspace->id)
            ->latest()
            ->take(6)
            ->pluck('title')
            ->map(fn($title) => "• " . $title)
            ->implode("\n");

        // 🔍 Step 0: Real-Time Threads Social Listening (Repliz Gold+ API Research across Target Keywords)
        $threadsService = app(ReplizService::class);
        $targetKeywords = ['ecommerce', 'jualanonline', 'marketplace', 'komisi marketplace', 'potongan admin'];
        $selectedKeyword = $targetKeywords[array_rand($targetKeywords)];

        $liveProspects = $threadsService->searchThreadsContent($selectedKeyword);
        $prospectInsights = '';
        if (! empty($liveProspects)) {
            $prospectInsights = "POSTINGAN THREADS REAL-TIME DARI KOMUNITAS (Keyword Target: {$selectedKeyword}):\n";
            foreach (array_slice($liveProspects, 0, 4) as $p) {
                $txt = $p['text'] ?? $p['description'] ?? $p['content'] ?? '';
                if (! empty($txt)) {
                    $prospectInsights .= "- \"" . mb_substr($txt, 0, 140) . "\"\n";
                }
            }
            $prospectInsights .= "Pelajari & tanggapi kebutuhan seller di atas secara relevan, solutif, dan tanpa ketergantungan marketplace!\n\n";
        }

        // 🧠 Step 0.5: Fetch Saved User Memory Rules (Directives sent by user via Telegram / UI)
        $userMemoryRules = AiGeneration::where('workspace_id', $workspace->id)
            ->where('type', 'user_memory_rule')
            ->latest()
            ->take(10)
            ->pluck('prompt')
            ->map(fn($rule) => "• " . $rule)
            ->implode("\n");

        $userMemoryContext = ! empty($userMemoryRules)
            ? "⚠️ CATATAN & INSTRUKSI KHUSUS PENGGUNA (WAJIB DITAATI 100%):\n{$userMemoryRules}\n\n"
            : "";

        $memoryContext = $userMemoryContext . $prospectInsights . (! empty($pastPostsSummary)
            ? "RIWAYAT POSTINGAN SEBELUMNYA (DILARANG Ulangi Angle/Hook Yang Sama):\n{$pastPostsSummary}\n\n"
            : "Ini adalah postingan awal kampanye. Buatkan konsep paling segar dan menarik.\n\n");

        // 🎯 Step 2: Content Pillar Rotation (Select 3 distinct pillars per run)
        $allPillars = [
            "Pilar 1: Branding Sosmed Mandiri (Solusi jualan di Instagram, TikTok, Threads, FB tanpa ketergantungan marketplace & bebas biaya admin tinggi)",
            "Pilar 2: Landing Page & Scrape Produk (Bikin toko online mandiri instan & fitur scrape produk cepat tanpa repot input manual)",
            "Pilar 3: 0% Komisi & Cashflow Lancar (Keuntungan 100% milik seller utuh, pembayaran QRIS & COD langsung ke rekening tanpa potongan)",
            "Pilar 4: Kedaulatan Bisnis & Data Pelanggan (Bangun aset & database pembeli toko sendiri di sosial media tanpa takut akun diblokir)",
            "Pilar 5: Edukasi & Solusi Seller (Belajar panduan sukses jualan mandiri di https://waperu.com/guides)",
            "Pilar 6: Storytelling Pejuang Rupiah (Kisah sukses seller yang bebas dari biaya komisi admin marketplace mahal)",
        ];

        // Shuffle & pick 3 pillars for today's 3 posts
        shuffle($allPillars);
        $selectedPillars = array_slice($allPillars, 0, 3);

        Log::info("Autonomous AI Learning Campaign Started with Social Listening", [
            'workspace' => $workspace->name,
            'selected_pillars' => $selectedPillars,
            'has_social_listening' => ! empty($prospectInsights),
        ]);

        // 📝 Step 3: Generate 3 Platform-Optimized Posts with High-Level Copywriting
        // Post 1: TikTok & IG Reels Video Script/Caption (09:00 WIB)
        $prompt1 = $memoryContext .
            "TUGAS KONTEN 1 (TikTok & IG Reels):\n" .
            "Sudut Pandang / Pilar: {$selectedPillars[0]}\n" .
            "Buatkan 1 caption TikTok & Reels yang santai, komunikatif, dan punya Hook kuat di kalimat pertama. " .
            "Gunakan emoji menarik (🚀, 💸, ✅). Tanpa markdown tebal **, tanpa kata pengantar AI.";

        $post1Content = $this->gemini->generate($prompt1, $brand);

        // Post 2: Instagram Feed & Facebook Edukasi Poster (15:00 WIB)
        $prompt2 = $memoryContext .
            "TUGAS KONTEN 2 (Instagram Feed & Facebook):\n" .
            "Sudut Pandang / Pilar: {$selectedPillars[1]}\n" .
            "Buatkan 1 caption Instagram Feed & Facebook yang edukatif, profesional, dan menjelaskan keunggulan 0% komisi {$brandName} dengan bullet points emoji. " .
            "Sertakan CTA ke {$brand->website_url}. Tanpa markdown tebal **, tanpa kata pengantar AI.";

        $post2Content = $this->gemini->generate($prompt2, $brand);

        // Post 3: Threads Storytelling Thread (20:00 WIB)
        $prompt3 = $memoryContext .
            "TUGAS KONTEN 3 (Threads Storytelling & Social Listening):\n" .
            "Sudut Pandang / Pilar: {$selectedPillars[2]}\n" .
            "Buatkan 1 cerita emosional & relatable (storytelling) untuk Threads tentang perjuangan seller online. " .
            "Gunakan gaya penulisan mengalir dan menyentuh. Tanpa markdown tebal **.";

        $post3Content = $this->gemini->generate($prompt3, $brand);

        // 🎨 Fetch banner media URL
        $bannerUrl = config('app.url', 'https://gotiau.com') . '/waperu-banner.png';
        $today = now()->format('Y-m-d');

        // 🚀 Create TikTok & Reels Post (09:00 WIB)
        $post1 = Post::create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brand?->id,
            'title' => "📹 TikTok & Reels — " . mb_substr($selectedPillars[0], 0, 60),
            'content' => $post1Content,
            'platforms' => ['tiktok', 'instagram'],
            'status' => 'scheduled',
            'scheduled_at' => "{$today} 09:00:00",
            'media_urls' => [$bannerUrl],
        ]);

        // 🚀 Create IG Feed & Facebook Post (15:00 WIB)
        $post2 = Post::create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brand?->id,
            'title' => "📸 IG Feed & Facebook — " . mb_substr($selectedPillars[1], 0, 60),
            'content' => $post2Content,
            'platforms' => ['instagram', 'facebook'],
            'status' => 'scheduled',
            'scheduled_at' => "{$today} 15:00:00",
            'media_urls' => [$bannerUrl],
        ]);

        // 🚀 Create Threads Thread Post (20:00 WIB)
        $post3 = Post::create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brand?->id,
            'title' => "🧵 Threads — " . mb_substr($selectedPillars[2], 0, 60),
            'content' => $post3Content,
            'platforms' => ['threads'],
            'status' => 'scheduled',
            'scheduled_at' => "{$today} 20:00:00",
            'media_urls' => [],
        ]);

        // 📊 Save Self-Learning Execution Memory Log
        $record = AiGeneration::create([
            'workspace_id' => $workspace->id,
            'type' => 'autonomous_workflow',
            'prompt' => $goal,
            'response_text' => "--- POST 1 (TikTok/Reels) ---\n\n{$post1Content}\n\n--- POST 2 (IG/FB) ---\n\n{$post2Content}\n\n--- POST 3 (Threads) ---\n\n{$post3Content}",
            'status' => 'completed',
            'metadata' => [
                'threads_social_listening' => $prospectInsights,
                'memory_recalled_posts' => $pastPostsSummary,
                'selected_pillars' => $selectedPillars,
                'post_ids' => [$post1->id, $post2->id, $post3->id],
                'learning_version' => '3.0_threads_social_listening',
            ],
        ]);

        return [
            'generation_id' => $record->id,
            'pillars_used' => $selectedPillars,
            'social_listening' => $prospectInsights,
            'copywriting' => "1. TikTok/Reels:\n{$post1Content}\n\n2. IG/FB:\n{$post2Content}\n\n3. Threads:\n{$post3Content}",
        ];
    }
}
