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
     * Run Autonomous Marketing Workflow for Waperu / Workspace
     */
    public function runAutonomousCampaign(Workspace $workspace, string $goal = 'Buat strategi marketing harian untuk menarik seller online ke Waperu'): array
    {
        $brand = $workspace->brands->first();
        $brandName = $brand->name ?? 'Waperu';

        // Step 1: Audience & Pain Point Research Agent
        $researchPrompt = "Analisis 3 masalah utama seller online di Indonesia (komisi tinggi, retur COD) dan solusi unggulan {$brandName}. Berikan poin ringkas.";
        $researchResult = $this->gemini->generate($researchPrompt, $brand);

        // Step 2: Generate 3 Distinct Platform-Tailored Posts
        $prompt1 = "Buatkan 1 caption TikTok & Instagram Reels yang santai (Hook seru + Solusi) untuk ajak seller jualan 0% komisi di {$brandName}. Tanpa markdown tebal **, tanpa kata pengantar AI.";
        $post1Content = $this->gemini->generate($prompt1, $brand);

        $prompt2 = "Buatkan 1 caption Instagram Feed & Facebook yang edukatif dan solutif tentang fitur Waperu (0% Komisi & COD lancar). Tanpa markdown tebal **, tanpa kata pengantar AI.";
        $post2Content = $this->gemini->generate($prompt2, $brand);

        $prompt3 = "Buatkan 1 teks storytelling relatable untuk Threads tentang capeknya seller dipotong komisi marketplace dan pentingnya punya toko mandiri di {$brandName}. Tanpa markdown tebal **.";
        $post3Content = $this->gemini->generate($prompt3, $brand);

        // Fetch existing media assets from library or fallback to banner
        $bannerUrl = config('app.url', 'https://gotiau.com') . '/waperu-banner.png';
        $today = now()->format('Y-m-d');

        // Create TikTok & Reels Video Post (09:00)
        Post::create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brand?->id,
            'title' => "📹 Post TikTok & IG Reels (09:00) — Edukasi Seller COD {$brandName}",
            'content' => $post1Content,
            'platforms' => ['tiktok', 'instagram'],
            'status' => 'scheduled',
            'scheduled_at' => "{$today} 09:00:00",
            'media_urls' => [$bannerUrl],
        ]);

        // Create IG Feed & Facebook Photo Poster Post (15:00)
        Post::create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brand?->id,
            'title' => "📸 Post IG Feed & Facebook (15:00) — Fitur Highlight {$brandName}",
            'content' => $post2Content,
            'platforms' => ['instagram', 'facebook'],
            'status' => 'scheduled',
            'scheduled_at' => "{$today} 15:00:00",
            'media_urls' => [$bannerUrl],
        ]);

        // Create Threads Text Thread Post (20:00)
        Post::create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brand?->id,
            'title' => "🧵 Post Threads (20:00) — Storytelling Seller Waperu",
            'content' => $post3Content,
            'platforms' => ['threads'],
            'status' => 'scheduled',
            'scheduled_at' => "{$today} 20:00:00",
            'media_urls' => [],
        ]);

        // Save execution log
        $record = AiGeneration::create([
            'workspace_id' => $workspace->id,
            'type' => 'autonomous_workflow',
            'prompt' => $goal,
            'response_text' => "--- KONTEN TIKTOK/REELS ---\n\n{$post1Content}\n\n--- KONTEN IG/FB ---\n\n{$post2Content}\n\n--- KONTEN THREADS ---\n\n{$post3Content}",
            'status' => 'completed',
            'metadata' => [
                'step_1_research' => $researchResult,
                'post_1' => $post1Content,
                'post_2' => $post2Content,
                'post_3' => $post3Content,
            ],
        ]);

        return [
            'generation_id' => $record->id,
            'research' => $researchResult,
            'copywriting' => "1. TikTok/Reels:\n{$post1Content}\n\n2. IG/FB:\n{$post2Content}\n\n3. Threads:\n{$post3Content}",
        ];
    }
}
