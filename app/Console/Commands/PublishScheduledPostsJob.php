<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ReplizService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishScheduledPostsJob extends Command
{
    protected $signature = 'posts:publish-scheduled';
    protected $description = 'Auto-publish postingan yang sudah memasuki waktu jadwal ke Repliz (Social Media)';

    public function handle(ReplizService $repliz, TelegramService $telegram): int
    {
        $duePosts = Post::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($duePosts->isEmpty()) {
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$duePosts->count()} postingan terjadwal untuk dipublikasikan...");

        foreach ($duePosts as $post) {
            $this->info("Mempublikasikan Post #{$post->id}: {$post->title}...");
            $success = $repliz->publish($post);

            if ($success) {
                $this->info("✅ Post #{$post->id} berhasil dipublikasikan via Repliz!");

                // Send Telegram Notification to Boss
                $platforms = is_array($post->platforms) ? implode(', ', array_map('ucfirst', $post->platforms)) : 'Social Media';
                $notifyText = "🎉 <b>Postingan Baru Saja Terbit Otonom!</b>\n\n" .
                    "📌 <b>Judul</b>: {$post->title}\n" .
                    "📱 <b>Target Channel</b>: {$platforms}\n" .
                    "🕒 <b>Waktu Terbit</b>: " . now()->format('d M Y H:i') . "\n\n" .
                    "<i>Konten telah dipublikasikan via Repliz Engine.</i>";

                // Notify via Telegram
                $telegram->sendMessage(env('TELEGRAM_ADMIN_CHAT_ID', ''), $notifyText);
            } else {
                $this->error("❌ Post #{$post->id} gagal dipublikasikan.");
            }
        }

        return Command::SUCCESS;
    }
}
