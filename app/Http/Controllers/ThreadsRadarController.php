<?php

namespace App\Http\Controllers;

use App\Models\AiGeneration;
use App\Models\Post;
use App\Services\OllamaService;
use App\Services\ReplizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
                ->map(function ($log) {
                    $metadata = $log->metadata ?? [];
                    $targetUrl = $metadata['target_url'] ?? null;
                    if (empty($targetUrl) || str_contains($targetUrl, '/search?q=')) {
                        $username = $metadata['username'] ?? 'seller_online';
                        $code = $metadata['target_post_id'] ?? 'DbSKJzAk4ww';
                        if ($code === 'sample1' || empty($code)) $code = 'DbSKJzAk4ww';
                        $metadata['target_url'] = "https://www.threads.net/@{$username}/post/{$code}";
                    }

                    // Clean response_text and drafted_reply of any preambles
                    $cleanResponse = OllamaService::cleanReplyDraft($log->response_text ?: ($metadata['drafted_reply'] ?? ''));
                    $log->response_text = $cleanResponse;
                    $metadata['drafted_reply'] = $cleanResponse;

                    // Always ensure conversation_threads timeline contains at least the initial AI draft and clean all assistant messages
                    if (empty($metadata['conversation_threads'])) {
                        $metadata['conversation_threads'] = [
                            [
                                'role' => 'assistant',
                                'content' => $cleanResponse,
                                'created_at' => $log->created_at?->toIso8601String() ?? now()->toIso8601String(),
                            ]
                        ];
                    } else {
                        foreach ($metadata['conversation_threads'] as &$msg) {
                            if (($msg['role'] ?? '') === 'assistant' && ! empty($msg['content'])) {
                                $msg['content'] = OllamaService::cleanReplyDraft($msg['content']);
                            }
                        }
                    }

                    $log->metadata = $metadata;
                    return $log;
                })
            : [];

        // Search live Threads posts via Repliz API & format direct permalinks (cached for 60s for speed)
        $cacheKey = 'threads_radar_search_' . md5($query);
        $rawLivePosts = Cache::remember($cacheKey, 60, function () use ($repliz, $query) {
            return $repliz->searchThreadsContent($query);
        });
        $liveThreadsPosts = array_map(function ($res) use ($query) {
            if (! is_array($res)) {
                return [
                    'text' => (string) $res,
                    'username' => 'seller_online',
                    'target_url' => 'https://www.threads.net',
                ];
            }

            $text = $res['text'] ?? $res['description'] ?? $res['content'] ?? $res['caption'] ?? '';
            $username = $res['user']['username'] ?? $res['user_name'] ?? $res['owner']['username'] ?? $res['author']['username'] ?? $res['username'] ?? 'seller';
            $code = $res['code'] ?? $res['shortcode'] ?? $res['pk'] ?? $res['id'] ?? null;
            $targetUrl = ReplizService::extractThreadsUrl($res, $query);

            return [
                'text' => $text,
                'username' => $username,
                'code' => $code,
                'target_url' => $targetUrl,
            ];
        }, $rawLivePosts);

        $activeKey = $repliz->getActiveApiKey();
        $hasReplizKey = ! empty($activeKey) && $activeKey !== 'repliz_live_dummy';

        return Inertia::render('ThreadsRadar/Index', [
            'query' => $query,
            'insights' => $insights,
            'prospectLogs' => $prospectLogs,
            'lastExecutedAt' => $prospectLogs->first()?->created_at?->toIso8601String(),
            'liveThreadsPosts' => $liveThreadsPosts,
            'hasReplizKey' => $hasReplizKey,
        ]);
    }

    public function triggerScan(Request $request)
    {
        @set_time_limit(120);

        $rawKeyword = $request->input('keyword');
        $keyword = is_string($rawKeyword) ? trim($rawKeyword) : null;
        $params = ! empty($keyword) ? ['keyword' => $keyword] : [];

        try {
            // Clear radar search cache so fresh live posts are fetched
            if (! empty($keyword)) {
                Cache::forget('threads_radar_search_' . md5($keyword));
            }

            Artisan::call('ollama:research', $params);

            return redirect()->back()->with('success', "⚡ Riset Threads & Local Ollama AI Berhasil Diterbitkan!");
        } catch (\Throwable $e) {
            Log::error("ThreadsRadar triggerScan Exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', "⚠️ Eksekusi Riset Terkendala: " . $e->getMessage());
        }
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

        $targetPostId = $request->input('target_post_id');
        $targetUrl = $request->input('target_url');

        // Check target_post_id from log if missing in request
        if (empty($targetPostId) && $request->log_id) {
            $log = AiGeneration::find($request->log_id);
            $targetPostId = $log?->metadata['target_post_id'] ?? $log?->metadata['post_id'] ?? null;
            $targetUrl = $targetUrl ?: ($log?->metadata['target_url'] ?? null);
        }

        // Clean any residual AI preamble text (e.g. "Berikut draf balasan...")
        $cleanContent = OllamaService::cleanReplyDraft($request->content);

        $post = Post::create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brand?->id,
            'title' => '🧵 Threads Reply Komen Seller - ' . now()->format('d M H:i'),
            'content' => $cleanContent,
            'platforms' => ['threads'],
            'status' => 'scheduled',
            'scheduled_at' => now(),
            'media_urls' => [],
            'metadata' => [
                'target_post_id' => $targetPostId,
                'target_url' => $targetUrl,
            ],
        ]);

        $published = false;

        // 💬 Call Repliz Create Comment API (POST /public/content/{contentId}/comment) to post comment directly on target Threads post
        if (! empty($targetPostId) && $targetPostId !== 'DbSKJzAk4ww' && $targetPostId !== 'sample1') {
            $published = $repliz->createComment($targetPostId, $cleanContent);
            if ($published) {
                $post->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);
            }
        }

        // Fallback to schedule publish if direct comment API was not applicable or returned false
        if (! $published) {
            $published = $repliz->publish($post);
        }

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
            return redirect()->back()->with('success', '🚀 Balasan Solutif Berhasil Dikomentarkan ke Threads Target!');
        }

        return redirect()->back()->with('error', '⚠️ Komentar Gagal Diterbitkan: ' . ($post->error_message ?? 'Periksa akun Repliz Anda.'));
    }

    public function saveFeedback(Request $request)
    {
        $request->validate([
            'feedback' => 'required|string|max:1000',
            'log_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return redirect()->back()->with('error', 'Workspace tidak ditemukan.');
        }

        $feedbackText = trim($request->feedback);

        // 1. Save rule to AI Memory database
        AiGeneration::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'type' => 'user_memory_rule',
            'prompt' => '🧠 Masukan & Evaluasi Pemilik Brand ke Ollama AI',
            'response_text' => $feedbackText,
            'status' => 'completed',
            'metadata' => [
                'source' => 'threads_radar_owner_feedback',
                'log_id' => $request->input('log_id'),
                'created_at' => now()->toIso8601String(),
            ],
        ]);

        // 2. Queue for background AI processing instead of blocking synchronous HTTP request
        $logId = $request->input('log_id');
        $queued = false;

        if ($logId) {
            $log = AiGeneration::where('workspace_id', $workspace->id)->find($logId);
            if ($log) {
                $metadata = $log->metadata ?? [];
                $history = $metadata['conversation_threads'] ?? [];

                if (empty($history)) {
                    $initialDraft = $log->response_text ?: ($metadata['drafted_reply'] ?? 'Draft Balasan Awal');
                    $history[] = [
                        'role' => 'assistant',
                        'content' => $initialDraft,
                        'created_at' => $log->created_at?->toIso8601String() ?? now()->toIso8601String(),
                    ];
                }

                // Push user feedback to conversation timeline
                $history[] = [
                    'role' => 'user',
                    'content' => $feedbackText,
                    'created_at' => now()->toIso8601String(),
                ];

                $metadata['conversation_threads'] = $history;
                $metadata['pending_ai_revision'] = true;
                $metadata['last_feedback'] = $feedbackText;

                $log->update(['metadata' => $metadata]);
                $queued = true;
            }
        }

        $msg = $queued
            ? "📥 Masukan Anda telah dimasukkan ke Antrian Ollama AI! Ollama akan merevisi & meng-upgrade balasan secara otomatis di background."
            : "🧠 Memori Pembelajaran Disimpan! Ollama AI akan selalu mengingat aturan ini: \"{$feedbackText}\"";

        return redirect()->back()->with('success', $msg);
    }

    public function deleteFeedback(AiGeneration $aiGeneration)
    {
        if ($aiGeneration->type === 'user_memory_rule') {
            $aiGeneration->delete();
            return redirect()->back()->with('success', '🗑️ Aturan memori pembelajaran AI berhasil dihapus.');
        }

        return redirect()->back()->with('error', 'Item bukan aturan memori.');
    }
}
