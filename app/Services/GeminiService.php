<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY', ''));
        $this->model = config('services.gemini.model', 'gemini-2.0-flash');
    }

    /**
     * Generate content using Gemini API with optional Brand context
     */
    public function generate(string $prompt, ?Brand $brand = null, string $systemInstruction = ''): string
    {
        if (empty($this->apiKey)) {
            return "🔑 GEMINI_API_KEY belum dikonfigurasi di file .env.\n\nSimulasi Balasan AI:\n" . $this->getMockResponse($prompt, $brand);
        }

        $contextPrefix = '';
        if ($brand) {
            $contextPrefix = "Anda adalah Autonomous AI Marketer untuk Brand berikut:\n" .
                "- Nama Brand: {$brand->name}\n" .
                "- Website: {$brand->website_url}\n" .
                "- Industri: {$brand->industry}\n" .
                "- Deskripsi: {$brand->description}\n" .
                "- Target Audiens: {$brand->target_audience}\n" .
                "- Tone of Voice / Gaya Bahasa: {$brand->tone_of_voice}\n\n" .
                "Tugas Anda adalah meriset target pasar, menyusun strategi marketing, atau membuat konten media sosial yang sangat efektif dan relevan dengan audiens brand tersebut.\n\n";
        }

        $fullPrompt = $contextPrefix . $prompt;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

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
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Tidak ada respon dari Gemini AI.';
            }

            Log::error('Gemini API Error', ['status' => $response->status(), 'body' => $response->body()]);
            return "⚠️ Terjadi kesalahan saat menghubungkan ke Gemini API (Status: {$response->status()}).";
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception', ['error' => $e->getMessage()]);
            return "⚠️ Error: {$e->getMessage()}";
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
