<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    protected string $baseUrl;
    protected string $defaultModel;

    public function __construct()
    {
        $this->baseUrl = env('OLLAMA_HOST', 'http://ollama:11434');
        $this->defaultModel = env('OLLAMA_MODEL', 'llama3.2:1b');
    }

    /**
     * Generate content using local Ollama model
     */
    public function generate(string $prompt, ?string $model = null): string
    {
        $selectedModel = $model ?: $this->defaultModel;
        $endpoint = rtrim($this->baseUrl, '/') . '/api/generate';

        try {
            Log::debug("Menggunakan Local Ollama Model: {$selectedModel}");

            $response = Http::timeout(60)->post($endpoint, [
                'model' => $selectedModel,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'num_thread' => 2,
                    'num_predict' => 250,
                ],
            ]);

            if ($response->successful()) {
                $text = $response->json('response');
                if (! empty($text)) {
                    return trim($text);
                }
            }

            Log::warning("Ollama API call status: {$response->status()}", ['body' => $response->body()]);
            return $this->fallbackPrompt($prompt);

        } catch (\Exception $e) {
            Log::warning("Ollama Exception (falling back): {$e->getMessage()}");
            return $this->fallbackPrompt($prompt);
        }
    }

    /**
     * Check if local Ollama container is running and healthy
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get(rtrim($this->baseUrl, '/') . '/api/version');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clean and extract ONLY the pure comment text from AI responses (Universal Preamble Stripper)
     */
    public static function cleanReplyDraft(string $rawText): string
    {
        $text = trim($rawText);

        // 1. Extract text after "Draf Balasan:", "Draft Balasan:", "Usulan Balasan:", etc.
        if (preg_match('/(?:berikut|tentu|ini)?\s*(?:adalah)?\s*(?:draf|draft|usulan|opsi|rekomendasi|contoh)?\s*(?:balasan|komentar|respon|response)\s*(?:tersebut|solutif)?\s*:\s*(.*)/is', $text, $matches)) {
            $extracted = trim($matches[1]);
            if (! empty($extracted)) {
                $text = $extracted;
            }
        }

        // 2. Strip surrounding quotes if present (e.g. "Halo Kak! ...")
        if (preg_match('/^["\'`«»““](.*)["\'`«»””]$/s', $text, $matches)) {
            $text = trim($matches[1]);
        }

        // 3. Filter out lines starting with conversational preambles
        $lines = explode("\n", $text);
        $cleanLines = [];
        $foundBody = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            // Check if line is conversational preamble
            $isPreamble = preg_match('/^(Berikut|Tentu|Berikutnya|Ini adalah|Ini dia|Draf|Draft|Respon|Response|Catatan|Saran|Halo! Berikut)\b/i', $trimmed)
                && ! str_contains($trimmed, 'Waperu')
                && ! str_contains($trimmed, '0%')
                && ! str_contains($trimmed, 'QRIS');

            if ($isPreamble && ! $foundBody) {
                continue;
            }

            $foundBody = true;
            $cleanLines[] = $line;
        }

        $result = ! empty($cleanLines) ? implode("\n", $cleanLines) : $text;

        // Clean quotes & markdown remnants
        $result = preg_replace('/^["\'`«»““]\s*/u', '', $result);
        $result = preg_replace('/\s*["\'`«»””]$/u', '', $result);
        $result = str_replace(['**', '###', 'As an AI', 'Sebagai AI'], '', $result);

        return trim($result, " \t\n\r\0\x0B\"'");
    }

    /**
     * Fallback to Gemini if Ollama model is downloading or unavailable
     */
    protected function fallbackPrompt(string $prompt): string
    {
        $gemini = app(GeminiService::class);
        return $gemini->generate($prompt);
    }
}
