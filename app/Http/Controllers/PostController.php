<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\GeminiService;
use App\Services\ReplizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        $posts = Post::where('workspace_id', $workspace?->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Post/Index', [
            'posts' => $posts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'platforms' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
        ]);

        $user = $request->user();
        $workspace = $user->currentWorkspace;
        $brand = $workspace?->brands->first();

        $status = ! empty($validated['scheduled_at']) ? 'scheduled' : 'draft';

        Post::create([
            'workspace_id' => $workspace?->id,
            'brand_id' => $brand?->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'platforms' => $validated['platforms'] ?? ['instagram', 'facebook', 'tiktok', 'twitter'],
            'status' => $status,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Konten berhasil disimpan sebagai ' . ucfirst($status) . '!');
    }

    public function publishNow(Post $post, ReplizService $repliz): RedirectResponse
    {
        $user = auth()->user();
        if ($post->workspace_id !== $user->current_workspace_id) {
            abort(403, 'Akses ditolak.');
        }

        $success = $repliz->publish($post);

        if ($success) {
            return redirect()->back()->with('success', '🚀 Konten Berhasil Dipublikasikan ke Social Media via Repliz!');
        }

        return redirect()->back()->with('error', 'Gagal mempublikasikan konten. Cek log/error.');
    }

    public function instantGenerateAndPublish(Request $request, GeminiService $gemini, ReplizService $repliz): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;
        $brand = $workspace?->brands->first();

        $aiPrompt = "Buatkan 1 konten promosi menarik untuk " . ($brand?->name ?? 'Waperu') . " yang siap diterbitkan hari ini di Instagram, TikTok, Facebook, dan X.";
        $content = $gemini->generate($aiPrompt, $brand);

        $post = Post::create([
            'workspace_id' => $workspace?->id,
            'brand_id' => $brand?->id,
            'title' => "Promosi Instant Test - " . now()->format('Y-m-d H:i'),
            'content' => $content,
            'platforms' => ['instagram', 'facebook', 'tiktok', 'twitter'],
            'status' => 'draft',
        ]);

        $success = $repliz->publish($post);

        if ($success) {
            return redirect()->back()->with('success', '🚀 Test Instant Post Berhasil Terkirim ke Repliz! (ID: ' . $post->repliz_post_id . ')');
        }

        return redirect()->back()->with('error', 'Gagal kirim ke Repliz: ' . ($post->error_message ?? 'Cek kunci API Repliz Anda'));
    }

    public function destroy(Post $post): RedirectResponse
    {
        $user = auth()->user();
        if ($post->workspace_id !== $user->current_workspace_id) {
            abort(403, 'Akses ditolak.');
        }

        $post->delete();

        return redirect()->back()->with('success', 'Postingan telah dihapus.');
    }
}
