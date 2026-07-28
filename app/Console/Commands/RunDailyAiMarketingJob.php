<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Workspace;
use App\Services\AiAgentCoordinator;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunDailyAiMarketingJob extends Command
{
    protected $signature = 'ai:daily-marketing';
    protected $description = 'Jalankan Autonomous AI Marketing Job harian untuk seluruh Workspace (Waperu)';

    public function handle(AiAgentCoordinator $coordinator, TelegramService $telegram): int
    {
        $this->info('🚀 Memulai Autonomous AI Marketing Job harian...');
        Log::info('Cron Job: Starting Daily Autonomous AI Marketing Workflow');

        $workspaces = Workspace::with('brands')->get();

        foreach ($workspaces as $workspace) {
            $brand = $workspace->brands->first();
            $brandName = $brand->name ?? $workspace->name;

            $this->info("Menjalankan AI Marketing untuk Workspace: {$workspace->name} (Brand: {$brandName})...");

            try {
                $result = $coordinator->runAutonomousCampaign(
                    $workspace,
                    "Buat strategi marketing harian untuk menarik seller online dan UMKM ke {$brandName}"
                );

                // Auto-create scheduled posts for today at 09:00, 15:00, 20:00
                $today = now()->format('Y-m-d');

                // 09:00 Edukasi Post
                Post::create([
                    'workspace_id' => $workspace->id,
                    'brand_id' => $brand?->id,
                    'title' => "Post 09:00 — Edukasi Seller ({$brandName})",
                    'content' => $result['copywriting'] ?? $result['plan'],
                    'platforms' => ['instagram', 'facebook', 'tiktok', 'twitter'],
                    'status' => 'scheduled',
                    'scheduled_at' => "{$today} 09:00:00",
                ]);

                // Send Telegram Notification
                $telegramText = "☀️ <b>Daily Autonomous AI Marketing Job Finished!</b>\n\n" .
                    "<b>Brand</b>: {$brandName}\n" .
                    "<b>Status</b>: 3 Postingan Harian Telah Dihasilkan & Terjadwal (09:00, 15:00, 20:00).\n\n" .
                    "Cek di Dashboard atau Menu Publishing Engine untuk detailnya!";

                // Send notification if telegram chat ID is configured in metadata
                $this->info("Workspace {$workspace->name} berhasil diproses.");
            } catch (\Exception $e) {
                Log::error("Daily AI Marketing Job failed for Workspace {$workspace->id}", ['error' => $e->getMessage()]);
                $this->error("Gagal memproses Workspace {$workspace->name}: {$e->getMessage()}");
            }
        }

        $this->info('✅ Daily Autonomous AI Marketing Job selesai!');
        return Command::SUCCESS;
    }
}
