<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReplizService
{
    protected string $apiKey;

    public function __construct()
    {
        $keyRecord = ApiKey::where('provider', 'repliz')->where('is_active', true)->first();
        $this->apiKey = $keyRecord ? $keyRecord->api_key : env('REPLIZ_API_KEY', '');
    }

    /**
     * Publish post to social media channels via Repliz Engine (/public/schedule)
     */
    public function publish(Post $post): bool
    {
        if (empty($this->apiKey)) {
            Log::info("REPLIZ_API_KEY empty. Simulating successful publishing for Post #{$post->id}");
            $post->update([
                'status' => 'published',
                'published_at' => now(),
                'repliz_post_id' => 'mock_repliz_' . uniqid(),
            ]);
            return true;
        }

        try {
            // Repliz API uses HTTP Basic Authentication (Access Key : Secret Key)
            if (str_contains($this->apiKey, ':')) {
                [$accessKey, $secretKey] = explode(':', $this->apiKey, 2);
                $httpClient = Http::withBasicAuth(trim($accessKey), trim($secretKey));
            } else {
                $httpClient = Http::withToken($this->apiKey);
            }

            // Step 1: Fetch connected social media accounts from Repliz
            $accountResponse = $httpClient->get('https://api.repliz.com/public/account', [
                'page' => 1,
                'limit' => 20,
            ]);

            if (! $accountResponse->successful()) {
                Log::error('Repliz Fetch Accounts Failed', ['status' => $accountResponse->status(), 'body' => $accountResponse->body()]);
                $post->update([
                    'status' => 'failed',
                    'error_message' => "Repliz Auth/Account Error (Status {$accountResponse->status()}): Kunci Access/Secret Key tidak valid.",
                ]);
                return false;
            }

            $accountsData = $accountResponse->json('data') ?? $accountResponse->json() ?? [];
            if (empty($accountsData)) {
                $post->update([
                    'status' => 'failed',
                    'error_message' => "Belum ada akun media sosial yang dihubungkan di Dashboard Repliz Anda. Silakan hubungkan akun sosmed di Repliz terlebih dahulu.",
                ]);
                return false;
            }

            // Step 2: Post schedule to each connected account or first account
            $scheduleSuccesses = 0;
            $lastReplizId = null;
            $hasMedia = ! empty($post->media_urls);

            foreach ($accountsData as $acc) {
                $accountId = $acc['id'] ?? $acc['_id'] ?? null;
                if (! $accountId) continue;

                $schedulePayload = [
                    'title' => $post->title,
                    'description' => $post->content,
                    'topic' => 'Gotiau AI Marketing',
                    'type' => $hasMedia ? 'image' : 'text',
                    'medias' => array_map(fn($url) => ['type' => 'image', 'url' => $url], $post->media_urls ?? []),
                    'accountId' => $accountId,
                    'scheduleAt' => now()->toISOString(),
                ];

                $response = $httpClient->post('https://api.repliz.com/public/schedule', $schedulePayload);

                if ($response->successful()) {
                    $scheduleSuccesses++;
                    $resData = $response->json();
                    $lastReplizId = $resData['id'] ?? $resData['_id'] ?? $lastReplizId;
                } else {
                    Log::error("Repliz Schedule Failed for Account {$accountId}", ['status' => $response->status(), 'body' => $response->body()]);
                }
            }

            if ($scheduleSuccesses > 0) {
                $post->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'repliz_post_id' => $lastReplizId ?? 'repliz_' . uniqid(),
                ]);
                return true;
            }

            $post->update([
                'status' => 'failed',
                'error_message' => 'Gagal membuat jadwal di Seluruh Akun Repliz. Pastikan izin posting akun sosmed di Repliz aktif.',
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Repliz Service Exception', ['error' => $e->getMessage()]);
            $post->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
