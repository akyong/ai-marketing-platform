<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

// Run Autonomous AI Marketing Job every morning at 07:00 AM
Schedule::command('ai:daily-marketing')->dailyAt('07:00');

// Auto-publish scheduled social media posts every minute via Repliz
Schedule::command('posts:publish-scheduled')->everyMinute();

// Fix & Attach All Existing Users to Waperu Workspace Command
Artisan::command('workspace:fix-all', function () {
    $workspace = Workspace::first();
    if (! $workspace) {
        $firstUser = User::first();
        $workspace = Workspace::create([
            'owner_id' => $firstUser ? $firstUser->id : 1,
            'name' => 'Waperu Workspace',
            'slug' => 'waperu',
        ]);
    }

    foreach (Workspace::all() as $ws) {
        if (empty($ws->widget_key)) {
            $ws->update([
                'widget_key' => 'ws_' . str_replace('-', '_', $ws->slug) . '_' . substr(md5(uniqid()), 0, 8),
            ]);
        }
    }

    $users = User::all();
    foreach ($users as $user) {
        if (! $user->workspaces()->where('workspace_id', $workspace->id)->exists()) {
            $user->workspaces()->attach($workspace->id, ['role' => 'owner']);
        }
        if (! $user->current_workspace_id) {
            $user->update(['current_workspace_id' => $workspace->id]);
        }
    }

    // Clean up & sanitize all existing posts content
    $repliz = new \App\Services\ReplizService();
    foreach (\App\Models\Post::all() as $post) {
        $clean = $repliz->sanitizeForSocialMedia($post->content);
        if ($clean !== $post->content) {
            $post->update(['content' => $clean]);
        }
    }

    $this->info("✅ Berhasil menghubungkan {$users->count()} akun user, memperbarui Widget Key, & membersihkan teks postingan!");
})->purpose('Menghubungkan seluruh akun user ke Waperu Workspace');

// Register Telegram Bot Webhook Command
Artisan::command('telegram:set-webhook {token?}', function (?string $token = null) {
    $telegram = new \App\Services\TelegramService($token);
    $webhookUrl = config('app.url', 'https://gotiau.com') . '/api/telegram/webhook';
    $result = $telegram->setWebhook($webhookUrl);

    if (! empty($result['ok'])) {
        $this->info("✅ Telegram Webhook Berhasil Terpasang! URL: {$webhookUrl}");
    } else {
        $this->error("❌ Gagal Memasang Webhook: " . ($result['description'] ?? 'Error Tidak Diketahui'));
    }
})->purpose('Memasang Webhook Telegram Bot ke Server Gotiau AI');
