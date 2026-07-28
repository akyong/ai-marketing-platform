<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $currentWorkspace = null;
        $workspaces = [];

        if ($user) {
            $workspaces = $user->workspaces()->get();

            // Auto-provision default Waperu Workspace if user has no workspace
            if ($workspaces->isEmpty()) {
                $workspace = Workspace::create([
                    'owner_id' => $user->id,
                    'name' => 'Waperu Workspace',
                    'slug' => 'waperu-' . substr(md5(uniqid()), 0, 4),
                ]);

                $workspace->users()->attach($user->id, ['role' => 'owner']);
                $user->update(['current_workspace_id' => $workspace->id]);

                Brand::create([
                    'workspace_id' => $workspace->id,
                    'name' => 'Waperu',
                    'website_url' => 'https://waperu.com',
                    'description' => 'Platform toko online mandiri 0% komisi untuk seller COD dan UMKM Indonesia.',
                    'industry' => 'E-Commerce Platform / SaaS',
                    'target_audience' => 'Online Sellers, Pejuang COD, UMKM, Retail Merchants',
                    'tone_of_voice' => 'Professional, Empathetic, Empowering, Direct',
                ]);

                $workspaces = $user->workspaces()->get();
            }

            if (! $user->current_workspace_id && $workspaces->isNotEmpty()) {
                $user->update(['current_workspace_id' => $workspaces->first()->id]);
            }

            if ($user->current_workspace_id) {
                $currentWorkspace = $user->currentWorkspace()->with('defaultBrand')->first();
                if ($currentWorkspace && empty($currentWorkspace->widget_key)) {
                    $generatedKey = 'ws_' . str_replace('-', '_', $currentWorkspace->slug) . '_' . substr(md5(uniqid()), 0, 8);
                    $currentWorkspace->update(['widget_key' => $generatedKey]);
                    $currentWorkspace = $currentWorkspace->fresh(['defaultBrand']);
                }
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'current_workspace' => $currentWorkspace,
                'workspaces' => $workspaces,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
