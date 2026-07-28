<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\WorkspaceController;
use App\Models\AiGeneration;
use App\Services\GeminiService;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $workspace = auth()->user()->currentWorkspace()->with('defaultBrand')->first();
    $generations = $workspace ? AiGeneration::where('workspace_id', $workspace->id)->latest()->take(10)->get() : [];

    return Inertia::render('Dashboard', [
        'generations' => $generations,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Workspace & Brand routes
    Route::get('/workspace/settings', [WorkspaceController::class, 'settings'])->name('workspace.settings');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('/workspaces/{workspace}/switch', [WorkspaceController::class, 'switchWorkspace'])->name('workspaces.switch');
    Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');

    // AI Generation Test Route
    Route::post('/ai/generate', function (Request $request, GeminiService $gemini) {
        $request->validate(['prompt' => 'required|string']);
        $user = $request->user();
        $workspace = $user->currentWorkspace()->with('defaultBrand')->firstOrFail();
        $brand = $workspace->brands->first();

        $aiResponse = $gemini->generate($request->prompt, $brand);

        AiGeneration::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'type' => 'web_test',
            'prompt' => $request->prompt,
            'response_text' => $aiResponse,
            'status' => 'completed',
        ]);

        return redirect()->back()->with('success', 'AI Marketing Strategy Berhasil Dihasilkan!');
    })->name('ai.generate');
});

// Telegram Webhook
Route::post('/api/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

require __DIR__.'/auth.php';
