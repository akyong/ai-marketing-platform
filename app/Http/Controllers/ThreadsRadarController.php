<?php

namespace App\Http\Controllers;

use App\Models\AiGeneration;
use App\Models\Post;
use App\Services\OllamaService;
use App\Services\ReplizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class ThreadsRadarController extends Controller
{
    public function index(Request $request, ReplizService $repliz): Response
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;
        $query = $request->input('query', 'ecommerce');

        $insights = $workspace
            ? AiGeneration::where('workspace_id', $workspace->id)
                ->whereIn('type', ['user_memory_rule', 'social_radar_scan', 'threads_social_listening'])
                ->latest()
                ->take(30)
                ->get()
            : [];

        $prospectLogs = $workspace
            ? AiGeneration::where('workspace_id', $workspace->id)
                ->where('type', 'ollama_social_prospecting')
                ->latest()
                ->take(50)
                ->get()
            : [];

        // Search live Threads posts via Repliz API
        $liveThreadsPosts = $repliz->searchThreadsContent($query);

        return Inertia::render('ThreadsRadar/Index', [
            'query' => $query,
            'insights' => $insights,
            'prospectLogs' => $prospectLogs,
            'lastExecutedAt' => $prospectLogs->first()?->created_at?->toIso8601String(),
            'liveThreadsPosts' => $liveThreadsPosts,
        ]);
    }

    public function triggerScan(Request $request)
    {
        $keyword = $request->input('keyword');
        $params = ! empty($keyword) ? ['keyword' => $keyword] : [];
        Artisan::call('ollama:research', $params);

        return redirect()->back()->with('success', "Riset Threads & Local Ollama AI Berhasil Dijalankan!");
    }

    public function replyNow(Request $request, ReplizService $repliz)
    {
        $request->validate([
            'content' => 'required|string',
            'log_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $workspace = $user->currentWorkspace()->with('defaultBrand')->firstOrFail();
        $brand = $workspace->brands->first();

        $post = Post::create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brand?->id,
            'title' => '🧵 Threads Prospect Reply - ' . now()->format('d M H:i'),
            'content' => $request->content,
            'platforms' => ['threads'],
            'status' => 'scheduled',
            'scheduled_at' => now(),
            'media_urls' => [],
        ]);

        $published = $repliz->publish($post);

        if ($request->log_id) {
            $log = AiGeneration::find($request->log_id);
            if ($log) {
                $metadata = $log->metadata ?? [];
                $metadata['is_published'] = true;
                $metadata['published_post_id'] = $post->id;
                $log->update(['metadata' => $metadata]);
            }
        }

        if ($published) {
            return redirect()->back()->with('success', '🚀 Balasan Solutif Ollama AI Berhasil Diterbitkan ke Threads!');
        }

        return redirect()->back()->with('error', '⚠️ Balasan Gagal Diterbitkan: ' . ($post->error_message ?? 'Periksa akun Repliz Anda.'));
    }
}
