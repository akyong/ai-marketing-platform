<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@waperu.com'],
            [
                'name' => 'Waperu Admin',
                'password' => bcrypt('password'),
            ]
        );

        $workspace = Workspace::firstOrCreate(
            ['slug' => 'waperu'],
            [
                'owner_id' => $user->id,
                'name' => 'Waperu Workspace',
            ]
        );

        if (! $workspace->users()->where('user_id', $user->id)->exists()) {
            $workspace->users()->attach($user->id, ['role' => 'owner']);
        }

        $user->update(['current_workspace_id' => $workspace->id]);

        Brand::firstOrCreate(
            ['workspace_id' => $workspace->id],
            [
                'name' => 'Waperu',
                'website_url' => 'https://waperu.com',
                'description' => 'Platform toko online mandiri 0% komisi untuk seller COD dan UMKM Indonesia.',
                'industry' => 'E-Commerce Platform / SaaS',
                'target_audience' => 'Online Sellers, Pejuang COD, UMKM, Retail Merchants',
                'tone_of_voice' => 'Professional, Empathetic, Empowering, Direct',
                'social_links' => [
                    'instagram' => 'https://instagram.com/waperu',
                    'facebook' => 'https://facebook.com/waperu',
                ],
            ]
        );
    }
}
