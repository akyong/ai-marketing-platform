<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function settings(Request $request): Response
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace()->with('brands')->firstOrFail();

        return Inertia::render('Workspace/Settings', [
            'workspace' => $workspace,
            'brand' => $workspace->brands->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'website_url' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(4),
        ]);

        $workspace->users()->attach($user->id, ['role' => 'owner']);

        Brand::create([
            'workspace_id' => $workspace->id,
            'name' => $validated['brand_name'] ?? $validated['name'],
            'website_url' => $validated['website_url'] ?? null,
            'industry' => $validated['industry'] ?? null,
        ]);

        $user->update(['current_workspace_id' => $workspace->id]);

        return redirect()->back()->with('success', 'Workspace berhasil dibuat.');
    }

    public function switchWorkspace(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();

        if (! $workspace->users()->where('user_id', $user->id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke Workspace ini.');
        }

        $user->update(['current_workspace_id' => $workspace->id]);

        return redirect()->back()->with('success', "Berpindah ke Workspace {$workspace->name}.");
    }
}
