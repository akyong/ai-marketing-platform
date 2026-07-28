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

        // Ultimate Fallback: Try .env GEMINI_API_KEY if available
        $envKey = config('services.gemini.key', env('GEMINI_API_KEY', ''));
        if (! empty($envKey)) {
            $envResult = $this->executeApiCallWithDetails($envKey, $prompt, $brand);
            if ($envResult['success']) {
                return $envResult['text'];
            }
            if (! empty($envResult['error'])) {
                $lastErrorMessage = $envResult['error'];
            }
        }

        return "⚠️ Gagal memanggil Google Gemini API.\n\nDetail Error: " . ($lastErrorMessage ?: 'Batas kuota 15 RPM tercapai.') . "\n\n" .
            "💡 Solusi: Buka https://aistudio.google.com/app/apikey dan tambahkan 1 API Key gratis lagi ke Key Pool (https://gotiau.com/api-keys).";
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
            $knowledgeBase = ! empty($brand->knowledge_base)
                ? $brand->knowledge_base
                : "1. Waperu.com adalah platform toko online mandiri yang membebaskan seller dari ketergantungan marketplace.\n" .
                  "2. Solusi Branding Sosmed: Seller bisa jualan & membangun brand sendiri di semua sosial media (Instagram, TikTok, Threads, Facebook).\n" .
                  "3. 0% Komisi: Keuntungan 100% milik seller, pembayaran QRIS & COD langsung ke rekening tanpa potongan admin.\n" .
                  "4. Landing Page Instan & Scrape Produk: Bikin toko online cepat dan bisa scrape/import produk tanpa repot input manual.\n" .
                  "5. Referensi Panduan Resmi: https://waperu.com/guides";

            $customRules = ! empty($brand->custom_rules)
                ? $brand->custom_rules
                : "1. KELUARKAN HANYA TEKS CAPTION/POSTINGAN AKHIR yang SIAP DITERBITKAN DENGAN RAPI.\n" .
                  "2. DILARANG KERAS menyertakan kata pengantar AI seperti 'Halo sebagai AI', 'Berikut analisis strategis', 'Panduan visual slide 1', 'Format:', atau penjelasan internal AI.\n" .
                  "3. DILARANG MENGAKU ATAU MENYEBUTKAN DIRI SEBAGAI AI, BOT, ATAU AUTOMATION. Menulislah 100% sebagai tim resmi / owner Waperu yang manusiawi, empati, dan solutif.\n" .
                  "4. DILARANG menggunakan tanda tebal markdown asterisks seperti **kata** atau ### Header (karena sosmed seperti Threads, Facebook, Instagram tidak mendukung sintaks markdown).\n" .
                  "5. Gunakan EMOJI yang menarik (🚀, 💸, ✅, 👋, 📦) dan buat paragraf yang rapi, mudah dibaca, serta kaitkan dengan keunggulan {$brand->name}.\n" .
                  "6. Sertakan Call to Action (CTA) ke website {$brand->website_url} dan Hashtag relevan di akhir postingan.";

            $contextPrefix = "Anda adalah Copywriter AI & Expert Marketer Profesional untuk Brand berikut:\n" .
                "- Nama Brand: {$brand->name}\n" .
                "- Website: {$brand->website_url} (Panduan: https://waperu.com/guides)\n" .
                "- Industri: {$brand->industry}\n" .
                "- Deskripsi: {$brand->description}\n" .
                "- Target Audiens: {$brand->target_audience}\n" .
                "- Tone of Voice: {$brand->tone_of_voice}\n\n" .
                "KNOWLEDGE BASE BRAND (DATABASE DYNAMIC MEMORY):\n" .
                "{$knowledgeBase}\n\n" .
                "PERATURAN PENTING PENULISAN KONTEN MEDIA SOSIAL:\n" .
                "{$customRules}\n\n";
        }

        $fullPrompt = $contextPrefix . $prompt;
        $modelsToTry = [
            'gemini-3.1-flash-lite',
            'gemini-2.0-flash',
            'gemini-1.5-flash',
            'gemini-1.5-pro',
        ];

        foreach ($modelsToTry as $modelName) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

            try {
                $response = Http::connectTimeout(15)->timeout(35)->post($url, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $fullPrompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.75,
                        'topP' => 0.95,
                        'topK' => 40,
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

                // If 429 Rate Limit (15 RPM limit), mark short 30-second cooldown, sleep 2s, and try next key
                if ($status === 429) {
                    if ($keyRecord) {
                        $keyRecord->markQuotaExceeded(30);
                    }
                    sleep(2);
                    continue;
                }

                // Try next model silently if 404 or 400
                if (in_array($status, [404, 400])) {
                    continue;
                }

                Log::error("Gemini Model {$modelName} Error", ['status' => $status, 'body' => $body]);

                $jsonError = $response->json('error.message') ?? $body;
                return ['success' => false, 'text' => null, 'error' => "Status {$status}: {$jsonError}"];
            } catch (\Exception $e) {
                Log::warning("Gemini Model {$modelName} Exception (retrying next model): {$e->getMessage()}");
                continue;
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
