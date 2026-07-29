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

        // Search live Threads posts via Repliz API
        $liveThreadsPosts = $repliz->searchThreadsContent($query);

        return Inertia::render('ThreadsRadar/Index', [
            'query' => $query,
            'insights' => $insights,
            'liveThreadsPosts' => $liveThreadsPosts,
        ]);
    }

    public function triggerScan(Request $request)
    {
        $keyword = $request->input('keyword', 'ecommerce');
        Artisan::call('ollama:research', ['keyword' => $keyword]);

        return redirect()->back()->with('success', "Riset Threads & Local Ollama AI untuk kata kunci '{$keyword}' Berhasil Dijalankan!");
    }
}
