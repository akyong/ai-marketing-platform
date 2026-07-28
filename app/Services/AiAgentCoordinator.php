<?php

namespace App\Services;

use App\Models\AiGeneration;
use App\Models\Brand;
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

        // Step 1: Audience & Pain Point Research Agent
        $researchPrompt = "LANGKAH 1: Riset Audiens & Masalah Seller\n" .
            "Tujuan: {$goal}\n" .
            "Analisislah 3 masalah utama seller online / UMKM di Indonesia (misal: komisi marketplace mahal, risiko COD) dan bagaimana fitur Waperu (0% komisi, QRIS langsung, API pengiriman) menjadi solusinya.";

        $researchResult = $this->gemini->generate($researchPrompt, $brand);

        // Step 2: Content Strategy & Schedule Planner Agent
        $plannerPrompt = "LANGKAH 2: Perencanaan Jadwal Konten Harian\n" .
            "Berdasarkan riset berikut:\n{$researchResult}\n\n" .
            "Susunlah jadwal 3 postingan harian untuk hari ini:\n" .
            "- Jam 09:00 (Post Edukasi)\n" .
            "- Jam 15:00 (Post Feature Highlight Solusi Waperu)\n" .
            "- Jam 20:00 (Post Social Proof / Testimonial Seller)";

        $planResult = $this->gemini->generate($plannerPrompt, $brand);

        // Step 3: Copywriting Agent (Instagram, TikTok, FB, X)
        $copywritingPrompt = "LANGKAH 3: Pembuatan Caption & Copywriting Siap Publish\n" .
            "Berdasarkan rencana jadwal berikut:\n{$planResult}\n\n" .
            "Buatkan caption lengkap dengan hashtag (#Waperu #PejuangRupiah #SellerCOD) dan Call-to-Action (CTA) untuk diposting ke Instagram, TikTok, Facebook, dan X.";

        $copywritingResult = $this->gemini->generate($copywritingPrompt, $brand);

        // Save execution to database
        $record = AiGeneration::create([
            'workspace_id' => $workspace->id,
            'type' => 'autonomous_workflow',
            'prompt' => $goal,
            'response_text' => "--- HASIL RISET AUDIENS ---\n\n" . $researchResult . "\n\n" .
                               "--- JADWAL KONTEN HARIAN ---\n\n" . $planResult . "\n\n" .
                               "--- CAPTION SIAP PUBLISH ---\n\n" . $copywritingResult,
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
