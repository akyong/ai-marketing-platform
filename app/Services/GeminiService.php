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
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
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
                $response = $this->executeApiCall($envKey, $prompt, $brand);
                if ($response !== null) {
                    return $response;
                }
            }

            return "🔑 Belum ada API Key Gemini yang tersimpan di database.\n\n" .
                "Silakan buka menu 🔑 Key Pool (https://gotiau.com/api-keys) dan masukkan API Key gratis dari https://aistudio.google.com/app/apikey";
        }

        $lastErrorMessage = '';

        // Rotate & Failover through keys in the pool
        foreach ($keys as $keyRecord) {
            Log::info("Menggunakan Gemini API Key: {$keyRecord->key_name} (Usage: {$keyRecord->usage_count})");

            $result = $this->executeApiCallWithDetails($keyRecord->api_key, $prompt, $brand, $keyRecord);

            if ($result['success']) {
                $keyRecord->markUsed();
                return $result['text'];
            }

            $lastErrorMessage = $result['error'];
            Log::warning("Gemini Key '{$keyRecord->key_name}' bermasalah: {$lastErrorMessage}");
        }

        return "⚠️ Gagal memanggil Google Gemini API.\n\nDetail Error: {$lastErrorMessage}\n\n" .
            "💡 Solusi: Buka https://aistudio.google.com/app/apikey dan pastikan API Key Anda aktif.";
    }

    /**
     * Get active non-cooldown keys from database
     */
    protected function getAvailableApiKeys(?int $workspaceId)
    {
        $keys = ApiKey::where('provider', 'gemini')
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

        // Fallback: If all active keys are currently on cooldown, unlock them and try anyway!
        if ($keys->isEmpty()) {
            $allActiveKeys = ApiKey::where('provider', 'gemini')
                ->where('is_active', true)
                ->get();

            if ($allActiveKeys->isNotEmpty()) {
                foreach ($allActiveKeys as $k) {
                    $k->update(['quota_exceeded_until' => null]);
                }
                return $allActiveKeys;
            }
        }

        return $keys;
    }

    /**
     * Execute HTTP Request to Gemini API with detailed error output
     */
    protected function executeApiCallWithDetails(string $apiKey, string $prompt, ?Brand $brand, ?ApiKey $keyRecord = null): array
    {
        $contextPrefix = '';
        if ($brand) {
            $contextPrefix = "Anda adalah Copywriter AI & Expert Marketer Profesional untuk Brand berikut:\n" .
                "- Nama Brand: {$brand->name}\n" .
                "- Website: {$brand->website_url}\n" .
                "- Industri: {$brand->industry}\n" .
                "- Deskripsi: {$brand->description}\n" .
                "- Target Audiens: {$brand->target_audience}\n" .
                "- Tone of Voice: {$brand->tone_of_voice}\n\n" .
                "PERATURAN PENTING PENULISAN KONTEN MEDIA SOSIAL:\n" .
                "1. KELUARKAN HANYA TEKS CAPTION/POSTINGAN AKHIR yang SIAP DITERBITKAN DENGAN RAPI.\n" .
                "2. DILARANG KERAS menyertakan kata pengantar AI seperti 'Halo sebagai AI', 'Berikut analisis strategis', 'Panduan visual slide 1', 'Format:', atau penjelasan internal AI.\n" .
                "3. DILARANG menggunakan tanda tebal markdown asterisks seperti **kata** atau ### Header (karena sosmed seperti Threads, Facebook, Instagram tidak mendukung sintaks markdown).\n" .
                "4. Gunakan EMOJI yang menarik (🚀, 💸, ✅, 👋, 📦) dan buat paragraf yang rapi, mudah dibaca, serta kaitkan dengan keunggulan {$brand->name}.\n" .
                "5. Sertakan Call to Action (CTA) ke website {$brand->website_url} dan Hashtag relevan di akhir postingan.\n\n";
        }

        $fullPrompt = $contextPrefix . $prompt;
        $modelsToTry = ['gemini-3.1-flash-lite', 'gemini-1.5-flash', 'gemini-2.0-flash', 'gemini-1.5-pro'];

        foreach ($modelsToTry as $modelName) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

            try {
                $response = Http::timeout(20)->post($url, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $fullPrompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                    ]
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if (! empty($text)) {
                        return ['success' => true, 'text' => $text, 'error' => null];
                    }
                }

                $status = $response->status();
                $body = $response->body();

                // If 429 Rate Limit
                if ($status === 429 && $keyRecord) {
                    $keyRecord->markQuotaExceeded(1);
                    return ['success' => false, 'text' => null, 'error' => "Rate Limit Hit (429)"];
                }

                Log::error("Gemini Model {$modelName} Error", ['status' => $status, 'body' => $body]);

                // Try next model if 404 or 400
                if (in_array($status, [404, 400])) {
                    continue;
                }

                $jsonError = $response->json('error.message') ?? $body;
                return ['success' => false, 'text' => null, 'error' => "Status {$status}: {$jsonError}"];
            } catch (\Exception $e) {
                Log::error('Gemini Service Exception', ['error' => $e->getMessage()]);
                return ['success' => false, 'text' => null, 'error' => $e->getMessage()];
            }
        }

        return ['success' => false, 'text' => null, 'error' => 'Seluruh model Gemini API tidak merespon'];
    }

    protected function executeApiCall(string $apiKey, string $prompt, ?Brand $brand, ?ApiKey $keyRecord = null): ?string
    {
        $result = $this->executeApiCallWithDetails($apiKey, $prompt, $brand, $keyRecord);
        return $result['success'] ? $result['text'] : null;
    }
}
