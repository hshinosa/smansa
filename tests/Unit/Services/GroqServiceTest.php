<?php

namespace Tests\Unit\Services;

use App\Models\AiSetting;
use App\Services\GroqService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class GroqServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        AiSetting::set('groq_api_keys', json_encode(['test-api-key']));
        AiSetting::set('groq_chat_model', 'llama-3.3-70b-versatile');
        AiSetting::set('groq_content_model', 'llama-3.3-70b-versatile');
        AiSetting::set('ai_max_tokens', 300);
        AiSetting::set('ai_temperature', 0.3);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_is_available_returns_true_when_api_keys_configured(): void
    {
        $service = new GroqService();
        $this->assertTrue($service->isAvailable());
    }

    public function test_is_available_returns_false_when_no_api_keys(): void
    {
        AiSetting::set('groq_api_keys', '[]');

        $service = new GroqService();
        $this->assertFalse($service->isAvailable());
    }

    public function test_chat_completion_returns_fallback_when_no_api_keys(): void
    {
        AiSetting::set('groq_api_keys', '[]');

        $service = new GroqService();
        $result = $service->chatCompletion([['role' => 'user', 'content' => 'Hello']]);

        $this->assertTrue($result['success']);
        $this->assertEquals('hardcoded_fallback', $result['provider']);
        $this->assertStringContainsString('gangguan teknis', $result['message']);
    }

    public function test_chat_completion_returns_error_on_rate_limit(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([], 429),
        ]);

        $service = new GroqService();
        $result = $service->chatCompletion([['role' => 'user', 'content' => 'Hello']]);

        $this->assertTrue($result['success']);
        $this->assertEquals('hardcoded_fallback', $result['provider']);
    }

    public function test_content_completion_uses_content_model(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Generated content']]
                ]
            ], 200),
        ]);

        $service = new GroqService();
        $result = $service->contentCompletion([['role' => 'user', 'content' => 'Write something']]);

        $this->assertTrue($result['success']);
    }

    public function test_create_embedding_returns_error(): void
    {
        $service = new GroqService();
        $result = $service->createEmbedding('test text');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('tidak support', $result['error']);
    }

    public function test_get_available_models_returns_empty_when_no_key(): void
    {
        AiSetting::set('groq_api_keys', '[]');

        $service = new GroqService();
        $result = $service->getAvailableModels();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_analyze_image_returns_error_for_nonexistent_file(): void
    {
        $service = new GroqService();
        $result = $service->analyzeImage('/nonexistent/path/image.jpg');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    public function test_analyze_image_handles_different_extensions(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile . '.png', 'fake image data');

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Image analysis result']]
                ]
            ], 200),
        ]);

        $service = new GroqService();
        $result = $service->analyzeImage($tempFile . '.png');

        unlink($tempFile . '.png');

        $this->assertTrue($result['success']);
    }

    public function test_completion_handles_empty_response(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => []
            ], 200),
        ]);

        $service = new GroqService();
        $result = $service->chatCompletion([['role' => 'user', 'content' => 'Hello']]);

        $this->assertTrue($result['success']);
        $this->assertEquals('hardcoded_fallback', $result['provider']);
    }

    public function test_completion_handles_4xx_error(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response(['error' => 'Bad request'], 400),
        ]);

        $service = new GroqService();
        $result = $service->chatCompletion([['role' => 'user', 'content' => 'Hello']]);

        $this->assertTrue($result['success']);
        $this->assertEquals('hardcoded_fallback', $result['provider']);
    }

    public function test_completion_handles_5xx_error(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response(['error' => 'Server error'], 500),
        ]);

        $service = new GroqService();
        $result = $service->chatCompletion([['role' => 'user', 'content' => 'Hello']]);

        $this->assertTrue($result['success']);
        $this->assertEquals('hardcoded_fallback', $result['provider']);
    }

    public function test_fallback_response_contains_ppdb_info_for_ppdb_queries(): void
    {
        AiSetting::set('groq_api_keys', '[]');

        $service = new GroqService();
        $result = $service->chatCompletion([['role' => 'user', 'content' => 'info PPDB sman 1 baleendah']]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('PPDB', $result['message']);
    }

    public function test_fallback_response_contains_biaya_info_for_biaya_queries(): void
    {
        AiSetting::set('groq_api_keys', '[]');

        $service = new GroqService();
        $result = $service->chatCompletion([['role' => 'user', 'content' => 'berapa biaya sekolah di sini']]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('GRATIS', $result['message']);
    }

    public function test_fallback_response_contains_alamat_info_for_alamat_queries(): void
    {
        AiSetting::set('groq_api_keys', '[]');

        $service = new GroqService();
        $result = $service->chatCompletion([['role' => 'user', 'content' => 'dimana lokasi sekolah']]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Lokasi', $result['message']);
    }

    public function test_fallback_response_contains_ekstra_info_for_ekstra_queries(): void
    {
        AiSetting::set('groq_api_keys', '[]');

        $service = new GroqService();
        $result = $service->chatCompletion([['role' => 'user', 'content' => 'ada ekstrakurikuler apa']]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Ekstrakurikuler', $result['message']);
    }

    public function test_fallback_response_contains_program_info_for_program_queries(): void
    {
        AiSetting::set('groq_api_keys', '[]');

        $service = new GroqService();
        $result = $service->chatCompletion([['role' => 'user', 'content' => 'program studi mipa']]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Program Studi', $result['message']);
    }

    public function test_completion_with_vision_format(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Vision result']]
                ]
            ], 200),
        ]);

        $service = new GroqService();
        $messages = [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => 'Describe this'],
                    ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,test']]
                ]
            ]
        ];
        $result = $service->chatCompletion($messages);

        $this->assertTrue($result['success']);
    }

    public function test_service_loads_settings_from_env_when_database_unavailable(): void
    {
        putenv('GROQ_API_KEY=env-api-key');

        AiSetting::query()->delete();

        $service = new GroqService();
        $this->assertTrue($service->isAvailable());

        putenv('GROQ_API_KEY');
    }
}
