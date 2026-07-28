<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Run Autonomous AI Marketing Job every morning at 07:00 AM
Schedule::command('ai:daily-marketing')->dailyAt('07:00');

// Auto-publish scheduled social media posts every 5 minutes via Repliz
Schedule::command('posts:publish-scheduled')->everyFiveMinutes();

// Fix & Attach All Existing Users to Waperu Workspace Command
Artisan::command('workspace:fix-all', function () {
    $workspace = Workspace::firstOrCreate(
        ['slug' => 'waperu'],
        [
            'owner_id' => 1,
            'name' => 'Waperu Workspace',
        ]
    );

    $users = User::all();
    foreach ($users as $user) {
        if (! $user->workspaces()->where('workspace_id', $workspace->id)->exists()) {
            $user->workspaces()->attach($workspace->id, ['role' => 'owner']);
        }
        $user->update(['current_workspace_id' => $workspace->id]);
    }

    $this->info("✅ Berhasil menghubungkan {$users->count()} akun user ke Waperu Workspace!");
})->purpose('Menghubungkan seluruh akun user ke Waperu Workspace');
