<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\AiAgentCoordinator;
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
            ->latest()
            ->get();

        return Inertia::render('Post/Index', [
            'posts' => $posts,
        ]);
    }

    public function generate(Request $request, AiAgentCoordinator $coordinator): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return redirect()->back()->with('error', 'Workspace tidak ditemukan');
        }

        $coordinator->runAutonomousCampaign(
            $workspace,
            "Buat strategi marketing harian untuk menarik seller online ke Waperu"
        );

        return redirect()->back()->with('success', '🚀 3 Campaign Baru Berhasil Dihasilkan & Dijadwalkan oleh Autonomous AI!');
    }

    public function publishNow(Post $post, ReplizService $repliz): RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->current_workspace_id && $post->workspace_id !== $user->current_workspace_id) {
            $post->update(['workspace_id' => $user->current_workspace_id]);
        }

        // Sanitize content before publishing
        $post->content = $repliz->sanitizeForSocialMedia($post->content);
        $post->save();

        $success = $repliz->publish($post);

        if ($success) {
            return redirect()->back()->with('success', '🚀 Konten Berhasil Dipublikasikan ke Social Media via Repliz!');
        }

        return redirect()->back()->with('error', 'Gagal mempublikasikan konten: ' . ($post->error_message ?? 'Cek koneksi/API Key Repliz Anda.'));
    }

    public function instantGenerateAndPublish(Request $request, AiAgentCoordinator $coordinator, ReplizService $repliz): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return redirect()->back()->with('error', 'Workspace tidak ditemukan');
        }

        // Trigger AI Agent Coordinator Engine (v3 with Social Listening & Memory)
        $coordinator->runAutonomousCampaign(
            $workspace,
            "Buat postingan promosi instan untuk Waperu"
        );

        // Pick the latest generated post
        $latestPost = Post::where('workspace_id', $workspace->id)
            ->latest()
            ->first();

        if (! $latestPost) {
            return redirect()->back()->with('error', 'Gagal membuat postingan instan');
        }

        // Sanitize content
        $latestPost->content = $repliz->sanitizeForSocialMedia($latestPost->content);
        $latestPost->save();

        $success = $repliz->publish($latestPost);

        if ($success) {
            return redirect()->back()->with('success', '🚀 Test Instant Post Berhasil Terkirim ke Repliz! (ID: ' . $latestPost->repliz_post_id . ')');
        }

        return redirect()->back()->with('error', 'Gagal kirim ke Repliz: ' . ($post->error_message ?? $latestPost->error_message ?? 'Cek kunci API Repliz Anda'));
    }

    public function destroy(Post $post): RedirectResponse
    {
        $user = auth()->user();
        if ($user->current_workspace_id && $post->workspace_id !== $user->current_workspace_id) {
            $post->update(['workspace_id' => $user->current_workspace_id]);
        }

        $post->delete();

        return redirect()->back()->with('success', 'Postingan berhasil dihapus');
    }
}
