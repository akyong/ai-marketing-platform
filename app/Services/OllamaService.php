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
            Log::info("Menggunakan Local Ollama Model: {$selectedModel}");

            $response = Http::timeout(60)->post($endpoint, [
                'model' => $selectedModel,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
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
            $response = Http::timeout(3)->get(rtrim($this->baseUrl, '/') . '/api/tags');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
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
