<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

// Run Autonomous AI Marketing Job every morning at 07:00 AM
Schedule::command('ai:daily-marketing')->dailyAt('07:00');

// Run Threads Social Radar Listening every hour (scans ecommerce, jualanonline, marketplace)
Schedule::command('threads:listen')->hourly();

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

    // Populate default Knowledge Base & Custom Rules for existing brands if empty
    foreach (\App\Models\Brand::all() as $b) {
        if (empty($b->knowledge_base)) {
            $b->update([
                'knowledge_base' => "1. Waperu.com adalah platform toko online mandiri yang membebaskan seller dari ketergantungan marketplace.\n" .
                    "2. Solusi Branding Sosmed: Seller bisa jualan & membangun brand sendiri di semua sosial media (Instagram, TikTok, Threads, Facebook).\n" .
                    "3. 0% Komisi: Keuntungan 100% milik seller, pembayaran QRIS & COD langsung ke rekening tanpa potongan admin.\n" .
                    "4. Landing Page Instan & Scrape Produk: Bikin toko online cepat dan bisa scrape/import produk tanpa repot input manual.\n" .
                    "5. Referensi Panduan Resmi: https://waperu.com/guides",
            ]);
        }
        if (empty($b->custom_rules)) {
            $b->update([
                'custom_rules' => "1. KELUARKAN HANYA TEKS CAPTION/POSTINGAN AKHIR yang SIAP DITERBITKAN DENGAN RAPI.\n" .
                    "2. DILARANG KERAS menyertakan kata pengantar AI seperti 'Halo sebagai AI', 'Berikut analisis strategis', 'Panduan visual slide 1', 'Format:', atau penjelasan internal AI.\n" .
                    "3. DILARANG MENGAKU ATAU MENYEBUTKAN DIRI SEBAGAI AI, BOT, ATAU AUTOMATION. Menulislah 100% sebagai tim resmi / owner Waperu yang manusiawi, empati, dan solutif.\n" .
                    "4. DILARANG menggunakan tanda tebal markdown asterisks seperti **kata** atau ### Header (karena sosmed seperti Threads, Facebook, Instagram tidak mendukung sintaks markdown).\n" .
                    "5. Gunakan EMOJI yang menarik (🚀, 💸, ✅, 👋, 📦) dan buat paragraf yang rapi, mudah dibaca, serta kaitkan dengan keunggulan Waperu.\n" .
                    "6. Sertakan Call to Action (CTA) ke website https://waperu.com dan Hashtag relevan di akhir postingan.",
            ]);
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
