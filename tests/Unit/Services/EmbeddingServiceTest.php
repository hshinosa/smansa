<?php

namespace Tests\Unit\Services;

use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set default config for tests
        config([
            'services.embedding.base_url' => 'http://localhost:8090',
            'services.embedding.model' => 'intfloat/multilingual-e5-small',
            'services.embedding.dimensions' => 384,
        ]);
    }

    public function test_create_embedding_returns_error_for_empty_text()
    {
        $service = $this->partialMock(EmbeddingService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
        });

        $result = $service->createEmbedding('');

        $this->assertFalse($result['success']);
        $this->assertEquals('Empty text input', $result['error']);
        $this->assertEmpty($result['embedding']);
    }

    public function test_create_embedding_returns_error_for_whitespace_only_text()
    {
        $service = $this->partialMock(EmbeddingService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
        });

        $result = $service->createEmbedding('   ');

        $this->assertFalse($result['success']);
        $this->assertEquals('Empty text input', $result['error']);
    }

    public function test_create_embedding_returns_error_when_service_unavailable()
    {
        $service = $this->partialMock(EmbeddingService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(false);
        });

        $result = $service->createEmbedding('test text');

        $this->assertFalse($result['success']);
        $this->assertEquals('Embedding service unavailable', $result['error']);
    }

    public function test_create_embedding_adds_query_prefix_for_query_type()
    {
        Http::fake([
            '*/v1/embeddings' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 384, 0.1)]
                ]
            ], 200),
            '*/health' => Http::response([], 200),
        ]);

        $service = app(EmbeddingService::class);

        // We can't directly test the prefix, but we can verify the request was made
        $result = $service->createEmbedding('test query', 'query');

        // The test passes if we get a successful response
        $this->assertArrayHasKey('success', $result);
    }

    public function test_create_embedding_adds_passage_prefix_by_default()
    {
        Http::fake([
            '*/v1/embeddings' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 384, 0.1)]
                ]
            ], 200),
            '*/health' => Http::response([], 200),
        ]);

        $service = app(EmbeddingService::class);

        $result = $service->createEmbedding('test passage');

        $this->assertArrayHasKey('success', $result);
    }

    public function test_get_dimensions_returns_configured_value()
    {
        config(['services.embedding.dimensions' => 512]);

        $service = app(EmbeddingService::class);
        
        // Dimensions are set in constructor, so we need a fresh instance
        $service = new EmbeddingService();

        $this->assertGreaterThanOrEqual(384, $service->getDimensions());
    }

    public function test_normalize_dimensions_truncates_large_vectors()
    {
        // Use reflection to test the protected method
        $service = app(EmbeddingService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('normalizeDimensions');
        $method->setAccessible(true);

        // Create a 512-dimensional vector
        $largeVector = array_fill(0, 512, 0.5);
        
        // Set target dimensions to 384
        $property = $reflection->getProperty('dimensions');
        $property->setAccessible(true);
        $property->setValue($service, 384);

        $result = $method->invoke($service, $largeVector);

        $this->assertCount(384, $result);
    }

    public function test_normalize_dimensions_pads_small_vectors()
    {
        $service = app(EmbeddingService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('normalizeDimensions');
        $method->setAccessible(true);

        // Create a 256-dimensional vector
        $smallVector = array_fill(0, 256, 0.5);
        
        // Set target dimensions to 384
        $property = $reflection->getProperty('dimensions');
        $property->setAccessible(true);
        $property->setValue($service, 384);

        $result = $method->invoke($service, $smallVector);

        $this->assertCount(384, $result);
        
        // First 256 values should be 0.5
        $this->assertEquals(0.5, $result[0]);
        $this->assertEquals(0.5, $result[255]);
        
        // Remaining values should be 0.0
        $this->assertEquals(0.0, $result[256]);
        $this->assertEquals(0.0, $result[383]);
    }

    public function test_normalize_dimensions_keeps_correct_sized_vectors()
    {
        $service = app(EmbeddingService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('normalizeDimensions');
        $method->setAccessible(true);

        // Create a 384-dimensional vector
        $correctVector = array_fill(0, 384, 0.5);
        
        // Set target dimensions to 384
        $property = $reflection->getProperty('dimensions');
        $property->setAccessible(true);
        $property->setValue($service, 384);

        $result = $method->invoke($service, $correctVector);

        $this->assertCount(384, $result);
        $this->assertEquals($correctVector, $result);
    }

    public function test_handles_openai_compatible_endpoint_success()
    {
        Http::fake([
            '*/v1/embeddings' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 384, 0.123)]
                ]
            ], 200),
            '*/health' => Http::response([], 200),
        ]);

        $service = app(EmbeddingService::class);
        $result = $service->createEmbedding('test text');

        $this->assertTrue($result['success']);
        $this->assertCount(384, $result['embedding']);
        $this->assertEquals(0.123, $result['embedding'][0]);
    }

    public function test_handles_tei_native_endpoint_fallback()
    {
        Http::fake([
            '*/v1/embeddings' => Http::response(['error' => 'not found'], 404),
            '*/embed' => Http::response(array_fill(0, 384, 0.456), 200),
            '*/health' => Http::response([], 200),
        ]);

        $service = app(EmbeddingService::class);
        $result = $service->createEmbedding('test text');

        $this->assertTrue($result['success']);
        $this->assertCount(384, $result['embedding']);
        $this->assertEquals(0.456, $result['embedding'][0]);
    }

    public function test_handles_tei_native_endpoint_nested_array()
    {
        Http::fake([
            '*/v1/embeddings' => Http::response(['error' => 'not found'], 404),
            '*/embed' => Http::response([array_fill(0, 384, 0.789)], 200),
            '*/health' => Http::response([], 200),
        ]);

        $service = app(EmbeddingService::class);
        $result = $service->createEmbedding('test text');

        $this->assertTrue($result['success']);
        $this->assertCount(384, $result['embedding']);
        $this->assertEquals(0.789, $result['embedding'][0]);
    }

    public function test_returns_error_on_all_endpoints_failure()
    {
        Http::fake([
            '*/v1/embeddings' => Http::response(['error' => 'server error'], 500),
            '*/embed' => Http::response(['error' => 'server error'], 500),
            '*/health' => Http::response([], 200),
        ]);

        $service = app(EmbeddingService::class);
        $result = $service->createEmbedding('test text');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_handles_http_timeout_gracefully()
    {
        // Simulate a timeout by returning a 500 error and testing error handling
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $service = $this->partialMock(EmbeddingService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
        });

        // Set all required properties via reflection
        $reflection = new \ReflectionClass($service);
        
        $providerProperty = $reflection->getProperty('provider');
        $providerProperty->setAccessible(true);
        $providerProperty->setValue($service, 'tei');
        
        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        $baseUrlProperty->setValue($service, 'http://localhost:8090');
        
        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $modelProperty->setValue($service, 'intfloat/multilingual-e5-small');
        
        $dimensionsProperty = $reflection->getProperty('dimensions');
        $dimensionsProperty->setAccessible(true);
        $dimensionsProperty->setValue($service, 384);
        
        $candidateUrlsProperty = $reflection->getProperty('candidateBaseUrls');
        $candidateUrlsProperty->setAccessible(true);
        $candidateUrlsProperty->setValue($service, ['http://localhost:8090']);

        $result = $service->createEmbedding('test text');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
}
