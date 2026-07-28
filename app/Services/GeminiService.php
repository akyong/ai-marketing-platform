<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\Brand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $model;

    public function __construct()
    {
        $this->model = config('services.gemini.model', 'gemini-2.0-flash');
    }

    /**
     * Generate content using Gemini API with Smart Key Rotation & Quota Failover
     */
    public function generate(string $prompt, ?Brand $brand = null): string
    {
        $workspaceId = $brand?->workspace_id;

        // Get available active keys from Database (ordered by least recently used)
        $keys = $this->getAvailableApiKeys($workspaceId);

        if ($keys->isEmpty()) {
            // Fallback to .env key if database key pool is empty
            $envKey = config('services.gemini.key', env('GEMINI_API_KEY', ''));
            if (! empty($envKey)) {
                return $this->executeApiCall($envKey, $prompt, $brand);
            }

            return "🔑 Belum ada API Key Gemini di Database atau .env.\n\nSimulasi Balasan AI:\n" . $this->getMockResponse($prompt, $brand);
        }

        // Rotate & Failover through keys in the pool
        foreach ($keys as $keyRecord) {
            Log::info("Menggunakan Gemini API Key: {$keyRecord->key_name} (Usage: {$keyRecord->usage_count})");

            $response = $this->executeApiCall($keyRecord->api_key, $prompt, $brand, $keyRecord);

            if ($response !== null) {
                // Success! Record usage
                $keyRecord->markUsed();
                return $response;
            }

            // If null was returned, quota limit or error occurred -> failover to next key!
            Log::warning("Gemini Key '{$keyRecord->key_name}' terkena limit quota / error. Beralih ke Key berikutnya...");
        }

        return "⚠️ Seluruh API Key Gemini di Pool sedang mengalami Kuota Limit / Error. Silakan tambahkan API Key baru di database.";
    }

    /**
     * Get active non-cooldown keys from database
     */
    protected function getAvailableApiKeys(?int $workspaceId)
    {
        return ApiKey::where('provider', 'gemini')
            ->where('is_active', true)
            ->where(function ($q) use ($workspaceId) {
                $q->whereNull('workspace_id');
                if ($workspaceId) {
                    $q->orWhere('workspace_id', $workspaceId);
                }
            })
            ->where(function ($q) {
                $q->whereNull('quota_exceeded_until')
                  ->orWhere('quota_exceeded_until', '<=', now());
            })
            ->orderByRaw('last_used_at ASC NULLS FIRST')
            ->get();
    }

    /**
     * Execute HTTP Request to Gemini API
     */
    protected function executeApiCall(string $apiKey, string $prompt, ?Brand $brand, ?ApiKey $keyRecord = null): ?string
    {
        $contextPrefix = '';
        if ($brand) {
            $contextPrefix = "Anda adalah Autonomous AI Marketer untuk Brand berikut:\n" .
                "- Nama Brand: {$brand->name}\n" .
                "- Website: {$brand->website_url}\n" .
                "- Industri: {$brand->industry}\n" .
                "- Deskripsi: {$brand->description}\n" .
                "- Target Audiens: {$brand->target_audience}\n" .
                "- Tone of Voice: {$brand->tone_of_voice}\n\n" .
                "Tugas Anda: Meriset target pasar dan membuat strategi marketing / konten terbaik.\n\n";
        }

        $fullPrompt = $contextPrefix . $prompt;
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$apiKey}";

        try {
            $response = Http::post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $fullPrompt]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Tidak ada respon teks dari Gemini AI.';
            }

            // If 429 Too Many Requests or 403 Quota Limit
            if (in_array($response->status(), [429, 403])) {
                if ($keyRecord) {
                    $keyRecord->markQuotaExceeded(1); // 1-hour cooldown for quota reset
                }
                return null; // Return null to trigger failover
            }

            Log::error('Gemini API Error', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function getMockResponse(string $prompt, ?Brand $brand = null): string
    {
        $brandName = $brand->name ?? 'Waperu';
        return "🤖 [Respon Simulasi AI untuk {$brandName}]\n\n" .
            "Berdasarkan analisis target audiens ({$brand->target_audience}), berikut ide strategi marketing:\n\n" .
            "1. **Edukasi**: Jualan Online COD 0% Komisi tanpa Potongan Marketplace.\n" .
            "2. **Solusi**: Cara Hubungkan QRIS & Ekspedisi Langsung ke Rekening Anda.\n\n" .
            "Perintah yang diterima: \"{$prompt}\"";
    }
}
