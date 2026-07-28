<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\WidgetChatController;
use App\Http\Controllers\WorkspaceController;
use App\Models\AiGeneration;
use App\Models\ApiKey;
use App\Models\Post;
use App\Services\AiAgentCoordinator;
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

    $stats = [
        'total_generations' => $workspace ? AiGeneration::where('workspace_id', $workspace->id)->count() : 0,
        'total_posts' => $workspace ? Post::where('workspace_id', $workspace->id)->count() : 0,
        'scheduled_posts' => $workspace ? Post::where('workspace_id', $workspace->id)->where('status', 'scheduled')->count() : 0,
        'published_posts' => $workspace ? Post::where('workspace_id', $workspace->id)->where('status', 'published')->count() : 0,
        'active_keys' => ApiKey::where('is_active', true)->count(),
    ];

    return Inertia::render('Dashboard', [
        'generations' => $generations,
        'stats' => $stats,
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

    // API Keys Pool routes
    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::patch('/api-keys/{apiKey}/toggle', [ApiKeyController::class, 'toggle'])->name('api-keys.toggle');
    Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    // CS Live Chat Widget Settings route
    Route::get('/widget/settings', function (Request $request) {
        $workspace = $request->user()->currentWorkspace;
        if ($workspace && empty($workspace->widget_key)) {
            $workspace->update([
                'widget_key' => 'ws_' . str_replace('-', '_', $workspace->slug) . '_' . substr(md5(uniqid()), 0, 8),
            ]);
            $workspace = $workspace->fresh();
        }
        return Inertia::render('Widget/Index', [
            'workspace' => $workspace,
            'brand' => $workspace?->brands->first(),
            'appUrl' => config('app.url', 'https://gotiau.com'),
        ]);
    })->name('widget.index');

    // Guide & Setup Handbook route
    Route::get('/guide', [\App\Http\Controllers\GuideController::class, 'index'])->name('guide.index');

    // System Log Viewer (laravel.log) routes
    Route::get('/logs', [\App\Http\Controllers\LogViewerController::class, 'index'])->name('logs.index');
    Route::post('/logs/clear', [\App\Http\Controllers\LogViewerController::class, 'clear'])->name('logs.clear');

    // Posts & Publishing Engine routes
    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::post('/posts/instant', [PostController::class, 'instantGenerateAndPublish'])->name('posts.instant');
    Route::post('/posts/{post}/publish', [PostController::class, 'publishNow'])->name('posts.publish');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // AI Generation Routes
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

    // Autonomous Campaign Workflow Route
    Route::post('/ai/autonomous-campaign', function (Request $request, AiAgentCoordinator $coordinator) {
        $user = $request->user();
        $workspace = $user->currentWorkspace()->with('defaultBrand')->firstOrFail();
        $goal = $request->input('goal', 'Buat strategi marketing harian untuk menarik seller online ke Waperu');

        $coordinator->runAutonomousCampaign($workspace, $goal);

        return redirect()->back()->with('success', 'Autonomous Campaign AI Berhasil Dijalankan!');
    })->name('ai.autonomous');
});

// Public Widget CS API
Route::post('/api/widget/chat', [WidgetChatController::class, 'handleChat'])->name('widget.chat');
Route::get('/api/widget/history', [WidgetChatController::class, 'getHistory'])->name('widget.history');

// Telegram Webhook
Route::post('/api/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

require __DIR__.'/auth.php';
