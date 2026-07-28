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
                    'error_message' => "Repliz Auth Error (Status {$accountResponse->status()}): Access/Secret Key tidak valid. Body: " . $accountResponse->body(),
                ]);
                return false;
            }

            $rawJson = $accountResponse->json();
            Log::info('Repliz GET /public/account Raw Response', ['raw' => $rawJson]);

            // Parse accounts array flexibly (Repliz uses Mongoose 'docs' pagination array)
            if (isset($rawJson['docs']) && is_array($rawJson['docs'])) {
                $accountsData = $rawJson['docs'];
            } elseif (isset($rawJson['data']) && is_array($rawJson['data'])) {
                $accountsData = $rawJson['data'];
            } elseif (isset($rawJson['accounts']) && is_array($rawJson['accounts'])) {
                $accountsData = $rawJson['accounts'];
            } elseif (isset($rawJson['items']) && is_array($rawJson['items'])) {
                $accountsData = $rawJson['items'];
            } elseif (is_array($rawJson) && isset($rawJson[0])) {
                $accountsData = $rawJson;
            } else {
                $accountsData = [];
            }

            if (empty($accountsData)) {
                $post->update([
                    'status' => 'failed',
                    'error_message' => "Belum ada akun media sosial yang terhubung di Dashboard Repliz. Respon Repliz: " . $accountResponse->body(),
                ]);
                return false;
            }

            // Step 2: Post schedule to each connected account
            $scheduleSuccesses = 0;
            $lastReplizId = null;
            $lastErrorDetails = [];
            $hasMedia = ! empty($post->media_urls);

            foreach ($accountsData as $acc) {
                if (! is_array($acc)) continue;

                $accountId = $acc['_id'] ?? $acc['id'] ?? $acc['generatedId'] ?? null;
                $platformType = strtolower($acc['type'] ?? $acc['platform'] ?? 'facebook');

                if (! $accountId) {
                    $lastErrorDetails[] = "ID Akun Kosong pada Objek Account";
                    continue;
                }

                // Instagram & TikTok require image/video media. If text-only, attach default brand image fallback.
                $bannerUrl = config('app.url', 'https://gotiau.com') . '/waperu-banner.png';
                $postType = $hasMedia ? 'image' : 'text';
                $mediasPayload = array_map(fn($url) => ['type' => 'image', 'url' => $url], $post->media_urls ?? []);

                if (! $hasMedia && in_array($platformType, ['instagram', 'tiktok', 'youtube'])) {
                    $postType = 'image';
                    $mediasPayload = [
                        [
                            'type' => 'image',
                            'url' => $bannerUrl,
                        ]
                    ];
                }

                $cleanContent = $this->sanitizeForSocialMedia($post->content, $platformType);
                $repliesPayload = [];
                $description = $cleanContent;

                // Handle platform-specific character limits & Threads nested thread splitting
                if ($platformType === 'threads') {
                    $chunks = $this->splitTextIntoChunks($cleanContent, 480);
                    $description = $chunks[0] ?? $cleanContent;

                    if (count($chunks) > 1) {
                        for ($i = 1; $i < count($chunks); $i++) {
                            $repliesPayload[] = [
                                'title' => '',
                                'description' => $chunks[$i],
                                'topic' => 'AI Marketing Thread',
                                'type' => 'text',
                                'medias' => [],
                                'status' => 'pending',
                            ];
                        }
                    }
                } elseif (in_array($platformType, ['instagram', 'tiktok', 'facebook', 'linkedin'])) {
                    // Instagram & TikTok max 2200 characters limit
                    if (mb_strlen($description) > 2180) {
                        $chunks = $this->splitTextIntoChunks($description, 2180);
                        $description = $chunks[0];
                    }
                }

                $scheduleTime = $post->scheduled_at ? $post->scheduled_at->toISOString() : now()->addMinutes(2)->toISOString();

                $schedulePayload = [
                    'title' => mb_substr($post->title, 0, 80),
                    'description' => $description,
                    'topic' => 'AI Marketing',
                    'type' => $postType,
                    'medias' => $mediasPayload,
                    'replies' => $repliesPayload,
                    'accountId' => $accountId,
                    'scheduleAt' => $scheduleTime,
                ];

                $response = $httpClient->post('https://api.repliz.com/public/schedule', $schedulePayload);

                if ($response->successful()) {
                    $scheduleSuccesses++;
                    $resData = $response->json();
                    $lastReplizId = $resData['id'] ?? $resData['_id'] ?? $lastReplizId;
                } else {
                    $errMsg = $response->json('message') ?? $response->json('error.message') ?? $response->json('error') ?? $response->body();
                    $lastErrorDetails[] = "[{$platformType} ({$accountId})]: Status {$response->status()} - {$errMsg}";
                    Log::error("Repliz Schedule Failed for Account {$accountId} ({$platformType})", ['status' => $response->status(), 'body' => $response->body()]);
                }
            }

            if ($scheduleSuccesses > 0) {
                $post->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'repliz_post_id' => $lastReplizId ?? 'repliz_' . uniqid(),
                    'error_message' => null,
                ]);
                return true;
            }

            $errorSummary = ! empty($lastErrorDetails)
                ? implode(' | ', $lastErrorDetails)
                : 'Gagal membuat jadwal di Seluruh Akun Repliz.';

            $post->update([
                'status' => 'failed',
                'error_message' => $errorSummary,
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

    /**
     * Smart text splitter by word boundaries without cutting words in half
     */
    protected function splitTextIntoChunks(string $text, int $maxChars = 480): array
    {
        $words = preg_split('/\s+/', $text);
        $chunks = [];
        $currentChunk = '';

        foreach ($words as $word) {
            if (empty($word)) continue;

            if (mb_strlen($currentChunk . ' ' . $word) > $maxChars) {
                if (! empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                }
                $currentChunk = $word;
            } else {
                $currentChunk = empty($currentChunk) ? $word : $currentChunk . ' ' . $word;
            }
        }

        if (! empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * Smart Social Media Text Sanitizer: strips AI meta-text, markdown syntax, and formats clean text
     */
    public function sanitizeForSocialMedia(string $text, string $platform = 'facebook'): string
    {
        // 1. Remove internal AI intros if present
        $text = preg_replace('/^(Halo!|Sebagai|Berikut|Berikut adalah).*?(menganalisis|strategi|konten).*?(\n\n|\n)/i', '', $text);
        $text = preg_replace('/###?\s*Analisis Strategis.*?\n\n/is', '', $text);
        $text = preg_replace('/####?\s*Panduan Visual.*$/is', '', $text);
        $text = preg_replace('/####?\s*\d+\.\s*Panduan Visual.*?\n/is', '', $text);
        $text = preg_replace('/(Format|Tema):\s*.*?\n/i', '', $text);

        // 2. Convert Markdown links [Text](URL) -> Text (URL) or just URL if identical
        $text = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/', function ($m) {
            return trim($m[1]) === trim($m[2]) ? $m[2] : "{$m[1]} ({$m[2]})";
        }, $text);

        // 3. Remove Markdown bold/italic syntax **word** / *word* / __word__ -> word
        $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text);
        $text = preg_replace('/\*([^*]+)\*/', '$1', $text);
        $text = preg_replace('/__([^_]+)__/', '$1', $text);
        $text = preg_replace('/_([^_]+)_/', '$1', $text);

        // 4. Remove Markdown headers (### Header -> Header)
        $text = preg_replace('/#{1,6}\s*/', '', $text);

        // 5. Remove horizontal rules (--- or ***)
        $text = preg_replace('/(?:\r?\n|\r)\s*[-*_]{3,}\s*(?:\r?\n|\r)/', "\n\n", $text);

        // 6. Clean multiple consecutive newlines
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }
}
