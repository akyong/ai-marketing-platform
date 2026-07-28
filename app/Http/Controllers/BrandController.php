<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $user = $request->user();

        // Check if user has access to the brand's workspace
        if (! $brand->workspace->users()->where('user_id', $user->id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke Brand ini.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website_url' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'tone_of_voice' => 'nullable|string',
            'social_links' => 'nullable|array',
        ]);

        $brand->update($validated);

        return redirect()->back()->with('success', 'Profil Brand berhasil diperbarui.');
    }
}
