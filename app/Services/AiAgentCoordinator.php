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
        $researchPrompt = "LANGKAH 1: Riset Audiens & Masalah Seller\n" .
            "Tujuan: {$goal}\n" .
            "Analisislah 3 masalah utama seller online / UMKM di Indonesia (misal: komisi marketplace mahal, risiko COD) dan bagaimana fitur Waperu (0% komisi, QRIS langsung, API pengiriman) menjadi solusinya.";

        $researchResult = $this->gemini->generate($researchPrompt, $brand);

        // Step 2: Content Strategy & Schedule Planner Agent
        $plannerPrompt = "LANGKAH 2: Perencanaan Jadwal Konten Harian Multi-Platform\n" .
            "Berdasarkan riset berikut:\n{$researchResult}\n\n" .
            "Susunlah 3 rencana konten harian spesifik platform:\n" .
            "- Post 1 (09:00 - TikTok & IG Reels): Script Video Pendek 15-detik + Hook Menarik.\n" .
            "- Post 2 (15:00 - Instagram Feed & Facebook): Visual Poster Edukasi + Caption.\n" .
            "- Post 3 (20:00 - Threads & X): Storytelling / Text Thread Pejuang Rupiah.";

        $planResult = $this->gemini->generate($plannerPrompt, $brand);

        // Step 3: Copywriting & Video Prompt Agent
        $copywritingPrompt = "LANGKAH 3: Pembuatan Script Video TikTok, Caption IG/FB, & Thread X\n" .
            "Berdasarkan rencana jadwal berikut:\n{$planResult}\n\n" .
            "Buatkan teks caption lengkap, hashtag (#Waperu #SellerCOD), CTA, dan konsep visual/video prompt untuk masing-masing platform.";

        $copywritingResult = $this->gemini->generate($copywritingPrompt, $brand);

        // Fetch existing media assets from library or fallback to AI placeholder
        $videoAsset = MediaAsset::where('workspace_id', $workspace->id)->where('type', 'video')->first();
        $imageAsset = MediaAsset::where('workspace_id', $workspace->id)->where('type', 'image')->first();

        $today = now()->format('Y-m-d');

        // Create TikTok & Reels Video Post (09:00)
        Post::create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brand?->id,
            'title' => "📹 Post TikTok & IG Reels (09:00) — Edukasi Seller COD {$brandName}",
            'content' => $copywritingResult,
            'platforms' => ['tiktok', 'instagram'],
            'status' => 'scheduled',
            'scheduled_at' => "{$today} 09:00:00",
            'media_urls' => [$videoAsset ? $videoAsset->file_url : 'https://waperu.com/assets/video_demo_seller.mp4'],
        ]);

        // Create IG Feed & Facebook Photo Poster Post (15:00)
        Post::create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brand?->id,
            'title' => "📸 Post IG Feed & Facebook (15:00) — Fitur Highlight {$brandName}",
            'content' => $copywritingResult,
            'platforms' => ['instagram', 'facebook'],
            'status' => 'scheduled',
            'scheduled_at' => "{$today} 15:00:00",
            'media_urls' => [$imageAsset ? $imageAsset->file_url : 'https://waperu.com/assets/poster_qris_0percent.jpg'],
        ]);

        // Create Threads & X Text Thread Post (20:00)
        Post::create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brand?->id,
            'title' => "🐦 Post Threads & X (20:00) — Storytelling Seller Waperu",
            'content' => $copywritingResult,
            'platforms' => ['twitter'],
            'status' => 'scheduled',
            'scheduled_at' => "{$today} 20:00:00",
            'media_urls' => [],
        ]);

        // Save execution log
        $record = AiGeneration::create([
            'workspace_id' => $workspace->id,
            'type' => 'autonomous_workflow',
            'prompt' => $goal,
            'response_text' => "--- HASIL RISET AUDIENS ---\n\n" . $researchResult . "\n\n" .
                               "--- JADWAL KONTEN MULTI-PLATFORM ---\n\n" . $planResult . "\n\n" .
                               "--- SCRIPT VIDEO & CAPTION SIAP PUBLISH ---\n\n" . $copywritingResult,
            'status' => 'completed',
            'metadata' => [
                'step_1_research' => $researchResult,
                'step_2_plan' => $planResult,
                'step_3_copywriting' => $copywritingResult,
            ],
        ]);

        return [
            'generation_id' => $record->id,
            'research' => $researchResult,
            'plan' => $planResult,
            'copywriting' => $copywritingResult,
        ];
    }
}
