<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        $apiKeys = ApiKey::where('workspace_id', $workspace?->id)
            ->orWhereNull('workspace_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('ApiKey/Index', [
            'apiKeys' => $apiKeys,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key_name' => 'required|string|max:255',
            'provider' => 'required|string|in:gemini,openai,claude,telegram,repliz',
            'api_key' => 'required|string',
        ]);

        $user = $request->user();
        $workspace = $user->currentWorkspace;

        $apiKey = ApiKey::create([
            'workspace_id' => $workspace?->id,
            'provider' => $validated['provider'],
            'key_name' => $validated['key_name'],
            'api_key' => $validated['api_key'],
            'is_active' => true,
        ]);

        if ($validated['provider'] === 'telegram') {
            $telegram = new \App\Services\TelegramService($validated['api_key']);
            $telegram->setWebhook(config('app.url', 'https://gotiau.com') . '/api/telegram/webhook');
        }

        return redirect()->back()->with('success', 'API Key berhasil ditambahkan ke Key Pool & Webhook terhubung!');
    }

    public function toggle(ApiKey $apiKey): RedirectResponse
    {
        $apiKey->update(['is_active' => ! $apiKey->is_active]);

        return redirect()->back()->with('success', 'Status API Key berhasil diperbarui.');
    }

    public function destroy(ApiKey $apiKey): RedirectResponse
    {
        $apiKey->delete();

        return redirect()->back()->with('success', 'API Key telah dihapus.');
    }
}
