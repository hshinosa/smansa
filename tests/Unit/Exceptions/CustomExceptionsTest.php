<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\ContentNotFoundException;
use App\Exceptions\AiServiceException;
use App\Exceptions\ScraperException;
use Tests\TestCase;

class CustomExceptionsTest extends TestCase
{
    // ==========================================
    // ContentNotFoundException Tests
    // ==========================================
    
    public function test_content_not_found_exception_default_message()
    {
        $exception = new ContentNotFoundException();
        
        $this->assertEquals('Content tidak ditemukan', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function test_content_not_found_exception_with_content_type()
    {
        $exception = new ContentNotFoundException('post');
        
        $this->assertEquals('Post tidak ditemukan', $exception->getMessage());
        $this->assertEquals('post', $exception->getContentType());
    }

    public function test_content_not_found_exception_with_identifier()
    {
        $exception = new ContentNotFoundException('gallery', 123);
        
        $this->assertStringContainsString('Gallery', $exception->getMessage());
        $this->assertStringContainsString('123', $exception->getMessage());
        $this->assertEquals('gallery', $exception->getContentType());
        $this->assertEquals(123, $exception->getIdentifier());
    }

    public function test_content_not_found_exception_custom_message()
    {
        $exception = new ContentNotFoundException('page', 'about', 'Halaman tidak ditemukan');
        
        $this->assertEquals('Halaman tidak ditemukan', $exception->getMessage());
    }

    public function test_content_not_found_exception_render_json()
    {
        $exception = new ContentNotFoundException('post', 'my-slug');
        
        $request = $this->mockRequest(true);
        $response = $exception->render($request);
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('CONTENT_NOT_FOUND', $response->getContent());
    }

    // ==========================================
    // AiServiceException Tests
    // ==========================================

    public function test_ai_service_exception_default_message()
    {
        $exception = new AiServiceException();
        
        $this->assertStringContainsString('AI', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
    }

    public function test_ai_service_exception_with_service_and_operation()
    {
        $exception = new AiServiceException('groq', 'chat');
        
        $this->assertEquals('groq', $exception->getService());
        $this->assertEquals('chat', $exception->getOperation());
    }

    public function test_ai_service_exception_with_context()
    {
        $exception = new AiServiceException('embedding', 'create', 'Test error', ['model' => 'e5-small']);
        
        $this->assertEquals('embedding', $exception->getService());
        $this->assertEquals('create', $exception->getOperation());
        $this->assertEquals(['model' => 'e5-small'], $exception->getContext());
    }

    public function test_ai_service_exception_groq_failed_factory()
    {
        $exception = AiServiceException::groqFailed('chat', 'Connection timeout', ['attempt' => 3]);
        
        $this->assertEquals('groq', $exception->getService());
        $this->assertEquals('chat', $exception->getOperation());
        $this->assertEquals(503, $exception->getCode());
        $this->assertStringContainsString('Connection timeout', $exception->getMessage());
    }

    public function test_ai_service_exception_embedding_failed_factory()
    {
        $exception = AiServiceException::embeddingFailed('Service unavailable');
        
        $this->assertEquals('embedding', $exception->getService());
        $this->assertEquals('create_embedding', $exception->getOperation());
        $this->assertEquals(503, $exception->getCode());
    }

    public function test_ai_service_exception_rag_search_failed_factory()
    {
        $exception = AiServiceException::ragSearchFailed('No results found');
        
        $this->assertEquals('rag', $exception->getService());
        $this->assertEquals('search', $exception->getOperation());
        $this->assertEquals(500, $exception->getCode());
    }

    public function test_ai_service_exception_rate_limit_exceeded_factory()
    {
        $exception = AiServiceException::rateLimitExceeded('groq', 120);
        
        $this->assertEquals('groq', $exception->getService());
        $this->assertEquals('rate_limit', $exception->getOperation());
        $this->assertEquals(429, $exception->getCode());
        $this->assertStringContainsString('120', $exception->getMessage());
    }

    public function test_ai_service_exception_render_json()
    {
        $exception = new AiServiceException('groq', 'chat', 'Test error');
        
        $request = $this->mockRequest(true);
        $response = $exception->render($request);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('AI_SERVICE_ERROR', $response->getContent());
    }

    // ==========================================
    // ScraperException Tests
    // ==========================================

    public function test_scraper_exception_default_message()
    {
        $exception = new ScraperException();
        
        $this->assertStringContainsString('scraper', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
    }

    public function test_scraper_exception_with_operation()
    {
        $exception = new ScraperException('login', 'Login failed');
        
        $this->assertEquals('login', $exception->getOperation());
    }

    public function test_scraper_exception_with_context()
    {
        $exception = new ScraperException('scrape', 'Test error', ['account' => 'bot1']);
        
        $this->assertEquals('scrape', $exception->getOperation());
        $this->assertEquals(['account' => 'bot1'], $exception->getContext());
    }

    public function test_scraper_exception_login_failed_factory()
    {
        $exception = ScraperException::loginFailed('Invalid credentials', ['username' => 'bot']);
        
        $this->assertEquals('login', $exception->getOperation());
        $this->assertEquals(401, $exception->getCode());
        $this->assertStringContainsString('Invalid credentials', $exception->getMessage());
    }

    public function test_scraper_exception_rate_limited_factory()
    {
        $exception = ScraperException::rateLimited(3600);
        
        $this->assertEquals('rate_limit', $exception->getOperation());
        $this->assertEquals(429, $exception->getCode());
        $this->assertStringContainsString('menit', $exception->getMessage());
    }

    public function test_scraper_exception_profile_not_found_factory()
    {
        $exception = ScraperException::profileNotFound('sman1baleendah');
        
        $this->assertEquals('profile_not_found', $exception->getOperation());
        $this->assertEquals(404, $exception->getCode());
        $this->assertStringContainsString('sman1baleendah', $exception->getMessage());
    }

    public function test_scraper_exception_connection_failed_factory()
    {
        $exception = ScraperException::connectionFailed('Timeout');
        
        $this->assertEquals('connection', $exception->getOperation());
        $this->assertEquals(503, $exception->getCode());
        $this->assertStringContainsString('Timeout', $exception->getMessage());
    }

    public function test_scraper_exception_processing_failed_factory()
    {
        $exception = ScraperException::processingFailed('post123', 'Image download failed');
        
        $this->assertEquals('processing', $exception->getOperation());
        $this->assertEquals(500, $exception->getCode());
        $this->assertStringContainsString('post123', json_encode($exception->getContext()));
    }

    public function test_scraper_exception_bot_account_inactive_factory()
    {
        $exception = ScraperException::botAccountInactive('Cookie expired');
        
        $this->assertEquals('bot_inactive', $exception->getOperation());
        $this->assertEquals(403, $exception->getCode());
    }

    public function test_scraper_exception_render_json()
    {
        $exception = new ScraperException('scrape', 'Test error');
        
        $request = $this->mockRequest(true);
        $response = $exception->render($request);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('SCRAPER_ERROR', $response->getContent());
    }

    // ==========================================
    // Throwable Tests
    // ==========================================

    public function test_exceptions_are_throwable()
    {
        $this->expectException(ContentNotFoundException::class);
        
        throw new ContentNotFoundException('test');
    }

    public function test_ai_service_exception_is_throwable()
    {
        $this->expectException(AiServiceException::class);
        
        throw new AiServiceException('test', 'operation');
    }

    public function test_scraper_exception_is_throwable()
    {
        $this->expectException(ScraperException::class);
        
        throw new ScraperException('test');
    }

    /**
     * Helper to mock a request with expectsJson
     */
    private function mockRequest(bool $expectsJson)
    {
        $request = \Illuminate\Http\Request::create('/test', 'GET');
        
        // For JSON requests, we need to set the proper headers
        if ($expectsJson) {
            $request->headers->set('Accept', 'application/json');
        }
        
        return $request;
    }
}
