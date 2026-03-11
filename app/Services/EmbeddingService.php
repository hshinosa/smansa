<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Embedding Service (self-hosted via Docker)
 * Default engine: HuggingFace Text Embeddings Inference (TEI)
 */
class EmbeddingService
{
    protected string $provider;
    protected string $baseUrl;
    protected string $model;
    protected int $dimensions;
    protected array $candidateBaseUrls = [];

    public function __construct()
    {
        $this->provider = (string) AiSetting::get('embedding_provider', env('EMBEDDING_PROVIDER', 'tei'));

        $configuredBaseUrl = rtrim((string) AiSetting::get('embedding_base_url', env('EMBEDDING_BASE_URL', 'http://embedding:8080')), '/');
        $envBaseUrl = rtrim((string) env('EMBEDDING_BASE_URL', ''), '/');

        $this->candidateBaseUrls = array_values(array_unique(array_filter([
            $configuredBaseUrl,
            $envBaseUrl,
            'http://embedding:8080',
            'http://localhost:8090',
        ])));

        $this->baseUrl = $this->resolveWorkingBaseUrl();
        $this->model = (string) AiSetting::get('embedding_model', env('EMBEDDING_MODEL', 'intfloat/multilingual-e5-small'));
        $this->dimensions = (int) AiSetting::get('embedding_dimensions', 384);
    }

    protected function resolveWorkingBaseUrl(): string
    {
        foreach ($this->candidateBaseUrls as $candidate) {
            if ($this->probeBaseUrl($candidate)) {
                return $candidate;
            }
        }

        return $this->candidateBaseUrls[0] ?? '';
    }

    protected function probeBaseUrl(string $baseUrl): bool
    {
        if (empty($baseUrl)) {
            return false;
        }

        try {
            $health = Http::timeout(2)->get("{$baseUrl}/health");
            if ($health->successful()) {
                return true;
            }
        } catch (\Exception $e) {
        }

        try {
            $root = Http::timeout(2)->get($baseUrl);
            return $root->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create embedding vector.
     * $inputType for E5-style models: query | passage
     */
    public function createEmbedding(string $text, string $inputType = 'passage'): array
    {
        $text = trim($text);
        if ($text === '') {
            return [
                'success' => false,
                'error' => 'Empty text input',
                'embedding' => [],
            ];
        }

        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Embedding service unavailable',
                'embedding' => [],
            ];
        }

        // E5 performs better with explicit prefixes
        $prefixedText = match ($inputType) {
            'query' => "query: {$text}",
            default => "passage: {$text}",
        };

        // 1) Try OpenAI-compatible endpoint (/v1/embeddings)
        $openAiCompat = $this->requestOpenAiCompatible($prefixedText);
        if ($openAiCompat['success']) {
            return $openAiCompat;
        }

        // 2) Fallback to TEI native endpoint (/embed)
        $teiNative = $this->requestTeiNative($prefixedText);
        if ($teiNative['success']) {
            return $teiNative;
        }

        Log::error('[EmbeddingService] Failed to generate embedding', [
            'provider' => $this->provider,
            'base_url' => $this->baseUrl,
            'model' => $this->model,
            'openai_error' => $openAiCompat['error'] ?? null,
            'tei_error' => $teiNative['error'] ?? null,
        ]);

        return [
            'success' => false,
            'error' => $teiNative['error'] ?? $openAiCompat['error'] ?? 'Embedding failed',
            'embedding' => [],
        ];
    }

    protected function requestOpenAiCompatible(string $text): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/v1/embeddings", [
                'input' => $text,
                'model' => $this->model,
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'OpenAI-compatible endpoint failed: HTTP ' . $response->status(),
                    'embedding' => [],
                ];
            }

            $json = $response->json();
            $embedding = $json['data'][0]['embedding'] ?? null;

            if (!is_array($embedding) || empty($embedding)) {
                return [
                    'success' => false,
                    'error' => 'OpenAI-compatible endpoint returned invalid embedding format',
                    'embedding' => [],
                ];
            }

            $normalized = $this->normalizeDimensions($embedding);

            return [
                'success' => true,
                'embedding' => $normalized,
                'provider' => $this->provider,
                'dimensions' => count($normalized),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'OpenAI-compatible endpoint exception: ' . $e->getMessage(),
                'embedding' => [],
            ];
        }
    }

    protected function requestTeiNative(string $text): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/embed", [
                'inputs' => $text,
                'truncate' => true,
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'TEI native endpoint failed: HTTP ' . $response->status(),
                    'embedding' => [],
                ];
            }

            $json = $response->json();

            // TEI may return either [float...] or [[float...]] depending on endpoint/version
            $embedding = [];
            if (is_array($json) && !empty($json) && is_numeric($json[0] ?? null)) {
                $embedding = $json;
            } elseif (is_array($json) && isset($json[0]) && is_array($json[0])) {
                $embedding = $json[0];
            }

            if (!is_array($embedding) || empty($embedding)) {
                return [
                    'success' => false,
                    'error' => 'TEI native endpoint returned invalid embedding format',
                    'embedding' => [],
                ];
            }

            $normalized = $this->normalizeDimensions($embedding);

            return [
                'success' => true,
                'embedding' => $normalized,
                'provider' => $this->provider,
                'dimensions' => count($normalized),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'TEI native endpoint exception: ' . $e->getMessage(),
                'embedding' => [],
            ];
        }
    }

    public function getDimensions(): int
    {
        return $this->dimensions;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function isAvailable(): bool
    {
        if ($this->probeBaseUrl($this->baseUrl)) {
            return true;
        }

        foreach ($this->candidateBaseUrls as $candidate) {
            if ($candidate === $this->baseUrl) {
                continue;
            }

            if ($this->probeBaseUrl($candidate)) {
                $this->baseUrl = $candidate;

                Log::warning('[EmbeddingService] Switched embedding base URL', [
                    'selected_base_url' => $this->baseUrl,
                ]);

                return true;
            }
        }

        Log::warning('[EmbeddingService] Health check failed for all embedding endpoints', [
            'candidates' => $this->candidateBaseUrls,
        ]);

        return false;
    }

    /**
     * Normalize vector dimensions to match DB schema.
     * - If larger than target: truncate
     * - If smaller than target: zero-pad
     */
    protected function normalizeDimensions(array $embedding): array
    {
        $current = count($embedding);
        $target = $this->dimensions;

        if ($current === $target) {
            return $embedding;
        }

        if ($current > $target) {
            return array_slice($embedding, 0, $target);
        }

        return array_merge($embedding, array_fill(0, $target - $current, 0.0));
    }
}
