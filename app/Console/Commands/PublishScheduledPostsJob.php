<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ReplizService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishScheduledPostsJob extends Command
{
    protected $signature = 'posts:publish-scheduled';
    protected $description = 'Auto-publish postingan yang sudah memasuki waktu jadwal ke Repliz (Social Media)';

    public function handle(ReplizService $repliz): int
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
            } else {
                $this->error("❌ Post #{$post->id} gagal dipublikasikan.");
            }
        }

        return Command::SUCCESS;
    }
}
