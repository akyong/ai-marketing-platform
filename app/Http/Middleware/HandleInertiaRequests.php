<?php

namespace App\Http\Middleware;

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

            if (!$user->current_workspace_id && $workspaces->isNotEmpty()) {
                $user->update(['current_workspace_id' => $workspaces->first()->id]);
            }

            if ($user->current_workspace_id) {
                $currentWorkspace = $user->currentWorkspace()->with('defaultBrand')->first();
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
