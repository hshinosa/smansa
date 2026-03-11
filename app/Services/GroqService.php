<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\AiSetting;

class GroqService
{
    protected const BASE_URL = 'https://api.groq.com/openai/v1';
    protected const CHAT_TIMEOUT = 120;
    protected const EMBEDDING_TIMEOUT = 30;

    protected array $apiKeys = [];
    protected string $chatModel;
    protected string $contentModel;
    protected int $currentKeyIndex = 0;

    public function __construct()
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        try {
            $keysJson = AiSetting::get('groq_api_keys', '[]');
            $this->apiKeys = is_array($keysJson) ? $keysJson : json_decode($keysJson, true) ?? [];
            $this->apiKeys = array_values(array_filter($this->apiKeys));

            $this->chatModel = AiSetting::get('groq_chat_model', 'llama-3.3-70b-versatile') ?: 'llama-3.3-70b-versatile';
            $this->contentModel = AiSetting::get('groq_content_model', 'llama-3.3-70b-versatile') ?: 'llama-3.3-70b-versatile';
        } catch (\Exception $e) {
            // Fallback to environment variable when database is unavailable
            $envKey = env('GROQ_API_KEY');
            if ($envKey) {
                $this->apiKeys = [$envKey];
                Log::info('[GroqService] Using API key from environment variable');
            } else {
                $this->apiKeys = [];
            }
            $this->chatModel = 'llama-3.3-70b-versatile';
            $this->contentModel = 'llama-3.3-70b-versatile';

            Log::warning('[GroqService] Could not load settings from database, using fallback', [
                'error' => $e->getMessage(),
                'has_env_key' => !empty($envKey),
            ]);
        }
    }

    protected function getApiKey(): ?string
    {
        if (empty($this->apiKeys)) {
            return null;
        }

        $key = $this->apiKeys[$this->currentKeyIndex % count($this->apiKeys)];
        $this->currentKeyIndex++;

        return $key;
    }

    protected function getModelForContext(string $context): string
    {
        return match ($context) {
            'content' => $this->contentModel,
            default => $this->chatModel,
        };
    }

    /**
     * Chat completion - used by ChatWidget/RAG
     */
    public function chatCompletion(array $messages, array $options = []): array
    {
        $context = $options['context'] ?? 'chat';
        return $this->completion($messages, $context, $options);
    }

    /**
     * Content generation completion - used by ContentCreationService
     */
    public function contentCompletion(array $messages, array $options = []): array
    {
        return $this->completion($messages, 'content', $options);
    }

    /**
     * Analyze image content using vision model
     * 
     * @param string $imagePath Absolute path to the image file
     * @param string $prompt Description prompt for the AI
     * @return array Result with success status and description
     */
    public function analyzeImage(string $imagePath, string $prompt = 'Describe this image in detail, focusing on people, activities, and school context.'): array
    {
        if (!file_exists($imagePath)) {
            return ['success' => false, 'error' => "Image not found at: {$imagePath}"];
        }

        try {
            $imageData = base64_encode(file_get_contents($imagePath));
            $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
            $mimeType = match (strtolower((string)$extension)) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };

            $messages = [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$imageData}",
                            ],
                        ],
                    ],
                ],
            ];

            return $this->completion($messages, 'vision', [
                'model' => 'llama-3.2-11b-vision-preview', // Keep trying vision or fallback
                'max_tokens' => 1000,
                'fallback_on_fail' => true, 
            ]);
        } catch (\Exception $e) {
            Log::error('[GroqService] Image analysis failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function completion(array $messages, string $context, array $options = []): array
    {
        $startTime = microtime(true);

        $this->loadSettings();

        $model = $options['model'] ?? $this->getModelForContext($context);
        $maxTokens = $options['max_tokens'] ?? (int) AiSetting::get('ai_max_tokens', $context === 'content' ? 2000 : 300);
        $temperature = $options['temperature'] ?? (float) AiSetting::get('ai_temperature', $context === 'content' ? 0.6 : 0.3);

        Log::info('[GroqService] Completion requested', [
            'context' => $context,
            'model' => $model,
            'num_messages' => count($messages),
            'max_tokens' => $maxTokens,
        ]);

        $triedKeys = 0;
        $totalKeys = count($this->apiKeys);

        if ($totalKeys === 0) {
            Log::error('[GroqService] No API keys configured');
            return $this->getHardcodedFallback($messages);
        }

        while ($triedKeys < $totalKeys) {
            $apiKey = $this->getApiKey();
            $triedKeys++;

            $result = $this->tryRequest($apiKey, $model, $messages, $maxTokens, $temperature, $options);

            if ($result['success']) {
                $elapsed = round((microtime(true) - $startTime) * 1000);
                $result['elapsed_ms'] = $elapsed;
                Log::info('[GroqService] Request successful', [
                    'provider' => 'groq',
                    'model' => $model,
                    'elapsed_ms' => $elapsed,
                    'key_index' => ($this->currentKeyIndex - 1) % $totalKeys,
                ]);
                return $result;
            }

            if ($result['error_type'] === 'rate_limit' && $triedKeys < $totalKeys) {
                Log::warning('[GroqService] Rate limited, trying next API key', [
                    'tried' => $triedKeys,
                    'total_keys' => $totalKeys,
                ]);
                continue;
            }

            // If we specifically requested NOT to fallback on fail
            if (!($options['fallback_on_fail'] ?? true)) {
                 return $result;
            }

            break;
        }

        $elapsed = round((microtime(true) - $startTime) * 1000);
        Log::error('[GroqService] All attempts failed', [
            'elapsed_ms' => $elapsed,
            'keys_tried' => $triedKeys,
        ]);

        return $this->getHardcodedFallback($messages);
    }

    protected function tryRequest(string $apiKey, string $model, array $messages, int $maxTokens, float $temperature, array $options = []): array
    {
        $startTime = microtime(true);

        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'top_p' => $options['top_p'] ?? 0.8,
                'stream' => false,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(self::CHAT_TIMEOUT)
              ->connectTimeout(30)
              ->post(self::BASE_URL . '/chat/completions', $payload);

            $elapsed = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();

                if (!isset($data['choices']) || empty($data['choices'])) {
                    return ['success' => false, 'error' => 'Empty response from Groq API', 'error_type' => 'empty'];
                }

                $message = $data['choices'][0]['message']['content'] ?? '';

                if (empty($message)) {
                    return ['success' => false, 'error' => 'Empty message from Groq API', 'error_type' => 'empty'];
                }

                return [
                    'success' => true,
                    'data' => $data,
                    'message' => $message,
                    'provider' => 'groq',
                    'model' => $model,
                    'elapsed_ms' => $elapsed,
                ];
            }

            $errorType = 'server';
            if ($response->status() === 429) {
                $errorType = 'rate_limit';
            } elseif ($response->status() >= 400 && $response->status() < 500) {
                $errorType = 'client';
            }

            Log::warning('[GroqService] Request failed', [
                'status' => $response->status(),
                'error_type' => $errorType,
                'body_preview' => substr($response->body(), 0, 200),
                'elapsed_ms' => $elapsed,
            ]);

            return ['success' => false, 'error' => 'Groq API error: ' . $response->status(), 'error_type' => $errorType];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $elapsed = round((microtime(true) - $startTime) * 1000);
            Log::error('[GroqService] Connection Exception', [
                'message' => $e->getMessage(),
                'elapsed_ms' => $elapsed,
            ]);
            return ['success' => false, 'error' => 'Connection timeout: ' . $e->getMessage(), 'error_type' => 'connection'];
        } catch (\Exception $e) {
            $elapsed = round((microtime(true) - $startTime) * 1000);
            Log::error('[GroqService] Exception', [
                'message' => $e->getMessage(),
                'type' => get_class($e),
                'elapsed_ms' => $elapsed,
            ]);
            return ['success' => false, 'error' => 'Exception: ' . $e->getMessage(), 'error_type' => 'exception'];
        }
    }

    /**
     * Create embedding using Groq API (not yet supported - placeholder)
     * Groq does not currently support embeddings, so we return a failure
     */
    public function createEmbedding(string $text): array
    {
        Log::warning('[GroqService] Embedding not supported by Groq API');
        return [
            'success' => false,
            'error' => 'Groq does not support embeddings. Configure an external embedding provider.',
            'embedding' => [],
        ];
    }

    /**
     * Check if service is available (has at least one API key)
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKeys);
    }

    /**
     * Get available models from Groq
     */
    public function getAvailableModels(): array
    {
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(10)->get(self::BASE_URL . '/models');

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('[GroqService] Failed to fetch models', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    protected function getHardcodedFallback(array $messages): array
    {
        $userQuery = '';
        if (is_array($messages)) {
            foreach (array_reverse($messages) as $msg) {
                if (isset($msg['role']) && $msg['role'] === 'user') {
                    $content = $msg['content'] ?? '';
                    if (is_array($content)) {
                        // Handle vision message format (array of objects)
                        foreach ($content as $part) {
                            if (isset($part['type']) && $part['type'] === 'text') {
                                $userQuery = strtolower($part['text'] ?? '');
                                break 2;
                            }
                        }
                    } else {
                        $userQuery = strtolower((string)$content);
                        break;
                    }
                }
            }
        }

        $response = 'Maaf, sistem AI sedang mengalami gangguan teknis (tidak dapat terhubung ke layanan AI). Silakan coba lagi dalam beberapa saat atau hubungi sekolah langsung.';

        if (str_contains($userQuery, 'ppdb') || str_contains($userQuery, 'pendaftaran') || str_contains($userQuery, 'daftar')) {
            $response = "**Informasi PPDB SMAN 1 Baleendah**\n\nMaaf, sistem AI sedang offline. Untuk informasi PPDB:\n\n- **Biaya:** GRATIS 100% (kebijakan Pemprov Jabar)\n- **Pendaftaran:** Melalui website PPDB Jawa Barat\n- **Jalur:** Zonasi, Afirmasi, Perpindahan Tugas Orang Tua, Prestasi\n\nHubungi sekolah untuk info lebih detail atau coba chat lagi nanti.";
        } elseif (str_contains($userQuery, 'biaya') || str_contains($userQuery, 'spp') || str_contains($userQuery, 'gratis')) {
            $response = "**Info Biaya Sekolah**\n\nMaaf, sistem AI sedang offline.\n\n**PENTING:** SMA Negeri se-Jawa Barat 100% GRATIS!\n\n- Tidak ada SPP/uang sekolah bulanan\n- Tidak ada biaya pendaftaran PPDB\n- Tidak ada pungutan wajib\n\nHubungi sekolah atau coba chat lagi nanti.";
        } elseif (str_contains($userQuery, 'alamat') || str_contains($userQuery, 'lokasi') || str_contains($userQuery, 'dimana')) {
            $response = "**Lokasi SMAN 1 Baleendah**\n\nMaaf, sistem AI sedang offline.\n\nUntuk informasi alamat dan kontak sekolah:\n\nCek halaman **Kontak** di website atau hubungi langsung ke sekolah.\n\nSilakan coba chat lagi nanti ya!";
        } elseif (str_contains($userQuery, 'ekstra') || str_contains($userQuery, 'kegiatan')) {
            $response = "**Info Ekstrakurikuler**\n\nMaaf, sistem AI sedang offline.\n\nSMAN 1 Baleendah memiliki berbagai ekstrakurikuler:\n- Olahraga (basket, voli, futsal, dll)\n- Seni (musik, tari, teater)\n- Sains (robotika, KIR)\n- Dan banyak lagi!\n\nHubungi sekolah untuk info lengkap atau coba chat lagi nanti.";
        } elseif (str_contains($userQuery, 'program') || str_contains($userQuery, 'jurusan') || str_contains($userQuery, 'mipa') || str_contains($userQuery, 'ips')) {
            $response = "**Program Studi SMAN 1 Baleendah**\n\nMaaf, sistem AI sedang offline.\n\nSMAN 1 Baleendah menawarkan 3 program peminatan:\n\n- **MIPA** - Matematika dan Ilmu Pengetahuan Alam\n- **IPS** - Ilmu Pengetahuan Sosial\n- **Bahasa** - Bahasa dan Sastra\n\nPemilihan peminatan dilakukan di kelas X berdasarkan minat, bakat, dan nilai rapor.";
        }

        return [
            'success' => true,
            'message' => $response,
            'provider' => 'hardcoded_fallback',
            'data' => [],
        ];
    }

    protected function maskSensitiveData(string $data): string
    {
        if (empty($data)) return 'EMPTY';

        if (strlen($data) > 10) {
            return substr($data, 0, 6) . '...' . substr($data, -4);
        }

        return $data;
    }
}
