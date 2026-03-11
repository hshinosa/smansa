<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ChatCacheService;
use Illuminate\Support\Facades\Cache;

class ChatCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear any existing cache before tests
        $cache = app(ChatCacheService::class);
        $cache->clear();
    }

    protected function tearDown(): void
    {
        // Clear cache after tests
        $cache = app(ChatCacheService::class);
        $cache->clear();
        parent::tearDown();
    }

    public function test_cache_key_generation(): void
    {
        $cache = app(ChatCacheService::class);
        
        $key1 = $cache->generateKey('Info PPDB?');
        $key2 = $cache->generateKey('info ppdb?');
        $key3 = $cache->generateKey('Berbeda?');
        
        // Same message with different cases should have different keys
        $this->assertNotEquals($key1, $key2);
        
        // Same message (lowercase) should have different keys from different messages
        $this->assertNotEquals($key1, $key3);
    }
    
    public function test_cache_key_with_context(): void
    {
        $cache = app(ChatCacheService::class);
        
        $key1 = $cache->generateKey('Test message');
        $key2 = $cache->generateKey('Test message', ['session' => '123']);
        $key3 = $cache->generateKey('Test message', ['session' => '456']);
        
        // Different contexts should have different keys
        $this->assertNotEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
        $this->assertNotEquals($key2, $key3); // Different contexts
    }

    public function test_cache_set_and_get(): void
    {
        $cache = app(ChatCacheService::class);
        $message = 'Berapa biaya PPDB?';
        $response = 'PPDB di SMA Negeri GRATIS!';
        
        // Should be empty initially
        $this->assertNull($cache->get($message));
        
        // Set cache
        $cache->set($message, $response);
        
        // Get cached response
        $cached = $cache->get($message);
        
        $this->assertEquals($response, $cached);
    }

    public function test_cache_miss_before_set(): void
    {
        $cache = app(ChatCacheService::class);
        $message = 'Pertanyaan baru';
        
        // Should return null before caching
        $result = $cache->get($message);
        
        $this->assertNull($result);
    }

    public function test_cache_invalidation(): void
    {
        $cache = app(ChatCacheService::class);
        $message = 'Info sekolah';
        $response = 'Lokasi: Jl. R.A.A. Wiranatakoesoemah No.30';
        
        // Set cache
        $cache->set($message, $response);
        $this->assertNotNull($cache->get($message));
        
        // Invalidate
        $cache->invalidate($message);
        
        // Should be null after invalidation
        $this->assertNull($cache->get($message));
    }

    public function test_cache_stats(): void
    {
        $cache = app(ChatCacheService::class);
        
        // Initially empty
        $stats = $cache->getStats();
        $this->assertEquals(0, $stats['size']);
        $this->assertEquals(0, $stats['hit_count']);
        $this->assertEquals(0, $stats['miss_count']);
        $this->assertEquals(0.0, $stats['hit_rate']);
        
        // Add some cache entries
        $cache->set('Test 1', 'Response 1');
        $this->assertEquals(1, $cache->getStats()['size']);
        
        $cache->set('Test 2', 'Response 2');
        $this->assertEquals(2, $cache->getStats()['size']);
        
        // Hit test
        $this->assertNotNull($cache->get('Test 1'));
        
        $stats = $cache->getStats();
        $this->assertEquals(1, $stats['hit_count']);
        $this->assertEquals(0, $stats['miss_count']);
        $this->assertEquals(1, $stats['total_requests']);
        $this->assertEquals(100.0, $stats['hit_rate'], '', 1);
        
        // Miss test
        $result = $cache->get('Pertanyaan belum pernah ditanyakan');
        $this->assertNull($result);
        
        $stats = $cache->getStats();
        $this->assertEquals(1, $stats['miss_count']);
        $this->assertEquals(2, $stats['total_requests']);
        $this->assertEquals(50.0, $stats['hit_rate'], '', 1);
    }

    public function test_lru_eviction_at_capacity(): void
    {
        $cache = app(ChatCacheService::class); // Create fresh instance
        
        // Mock small cache size for testing
        $reflection = new \ReflectionClass($cache);
        $maxSizeProp = $reflection->getProperty('maxSize');
        $maxSizeProp->setAccessible(true);
        $maxSizeProp->setValue($cache, 3); // Only allow 3 cached responses
        
        try {
            // Add 5 items, which should trigger eviction of 2 oldest
            for ($i = 1; $i <= 5; $i++) {
                $cache->set("Msg {$i}", "Response {$i}");
            }
            
            $stats = $cache->getStats();
            $this->assertEquals(3, $stats['size'], 'Cache should maintain max size of 3');
            
            // Ensure cache size maintained and at least one oldest entry evicted
            $this->assertNull($cache->get('Msg 1'));
            $remaining = 0;
            $remaining += $cache->get('Msg 2') ? 1 : 0;
            $remaining += $cache->get('Msg 3') ? 1 : 0;
            $remaining += $cache->get('Msg 4') ? 1 : 0;
            $remaining += $cache->get('Msg 5') ? 1 : 0;
            $this->assertGreaterThanOrEqual(2, $remaining);
            
        } finally {
            // Reset max size
            $maxSizeProp->setValue($cache, 500);
        }
    }

    public function test_clear_all_cache(): void
    {
        $cache = app(ChatCacheService::class); // Create fresh instance
        
        // Add some cache entries
        $cache->set('Msg 1', 'Response 1');
        $cache->set('Msg 2', 'Response 2');
        
        $this->assertEquals(2, $cache->getStats()['size']);
        
        // Clear all
        $cache->clear();
        
        $this->assertEquals(0, $cache->getStats()['size']);
        $this->assertEquals(0, $cache->getStats()['hit_count']);
        $this->assertEquals(0, $cache->getStats()['miss_count']);
    }

    public function test_ttl_expiration(): void
    {
        $cache = app(ChatCacheService::class); // Create fresh instance
        
        $reflection = new \ReflectionClass($cache);
        $ttlProp = $reflection->getProperty('ttl');
        $ttlProp->setAccessible(true);
        $ttlProp->setValue($cache, 1); // 1 second TTL for testing
        
        try {
            $cache->set('Temporary message', 'Temporary response');
            
            // Should be available immediately
            $this->assertNotNull($cache->get('Temporary message'));
            
            // Wait for expiration
            sleep(2);
            
            // Should be gone after TTL
            $this->assertNull($cache->get('Temporary message'));
            
        } finally {
            // Reset TTL
            $ttlProp->setValue($cache, 3600);
        }
    }
}
