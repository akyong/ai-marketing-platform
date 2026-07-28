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
     * Publish post to social media channels via Repliz Engine
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

            $response = $httpClient->post('https://api.repliz.com/public/post', [
                'title' => $post->title,
                'caption' => $post->content,
                'platforms' => $post->platforms ?? ['instagram', 'facebook'],
                'media_urls' => $post->media_urls ?? [],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $post->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'repliz_post_id' => $data['id'] ?? $data['_id'] ?? 'repliz_' . uniqid(),
                ]);
                return true;
            }

            Log::error('Repliz Publishing Failed', ['status' => $response->status(), 'body' => $response->body()]);
            $post->update([
                'status' => 'failed',
                'error_message' => "Repliz Error (Status {$response->status()}): {$response->body()}",
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
