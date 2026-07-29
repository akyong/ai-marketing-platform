<?php

namespace App\Http\Controllers;

use App\Models\AiGeneration;
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
}
