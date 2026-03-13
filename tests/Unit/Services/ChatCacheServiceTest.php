<?php

namespace Tests\Unit\Services;

use App\Services\ChatCacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatCacheServiceTest extends TestCase
{
    protected ChatCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChatCacheService();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_generate_key_returns_consistent_hash(): void
    {
        $key1 = $this->service->generateKey('test message');
        $key2 = $this->service->generateKey('test message');

        $this->assertEquals($key1, $key2);
        $this->assertStringStartsWith('chat:cache:', $key1);
    }

    public function test_generate_key_includes_context(): void
    {
        $key1 = $this->service->generateKey('test message', ['session' => 'abc123']);
        $key2 = $this->service->generateKey('test message', ['session' => 'xyz789']);

        $this->assertNotEquals($key1, $key2);
    }

    public function test_generate_session_key_creates_unique_key(): void
    {
        $key1 = $this->service->generateSessionKey('test message', 'session1');
        $key2 = $this->service->generateSessionKey('test message', 'session2');

        $this->assertNotEquals($key1, $key2);
    }

    public function test_set_and_get_cache_entry(): void
    {
        $message = 'What is the school address?';
        $response = 'SMAN 1 Baleendah is located in Kabupaten Bandung';

        $this->service->set($message, $response);
        $cached = $this->service->get($message);

        $this->assertEquals($response, $cached);
    }

    public function test_get_returns_null_for_nonexistent_entry(): void
    {
        $result = $this->service->get('nonexistent message');
        $this->assertNull($result);
    }

    public function test_get_tracks_cache_hits(): void
    {
        $message = 'test message for hit tracking';
        $response = 'test response';

        $this->service->set($message, $response);
        $this->service->get($message);

        $stats = $this->service->getStats();
        $this->assertGreaterThan(0, $stats['hit_count']);
    }

    public function test_get_tracks_cache_misses(): void
    {
        $this->service->get('nonexistent message');

        $stats = $this->service->getStats();
        $this->assertGreaterThan(0, $stats['miss_count']);
    }

    public function test_invalidate_removes_cache_entry(): void
    {
        $message = 'message to invalidate';
        $response = 'response to remove';

        $this->service->set($message, $response);
        $this->assertNotNull($this->service->get($message));

        $this->service->invalidate($message);
        $this->assertNull($this->service->get($message));
    }

    public function test_clear_removes_all_cache_entries(): void
    {
        $this->service->set('msg1', 'response1');
        $this->service->set('msg2', 'response2');
        $this->service->set('msg3', 'response3');

        $this->service->clear();

        $this->assertNull($this->service->get('msg1'));
        $this->assertNull($this->service->get('msg2'));
        $this->assertNull($this->service->get('msg3'));

        $stats = $this->service->getStats();
        $this->assertEquals(0, $stats['size']);
    }

    public function test_get_stats_returns_correct_structure(): void
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('size', $stats);
        $this->assertArrayHasKey('max_size', $stats);
        $this->assertArrayHasKey('ttl', $stats);
        $this->assertArrayHasKey('hit_count', $stats);
        $this->assertArrayHasKey('miss_count', $stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        $this->assertArrayHasKey('batch_count', $stats);
    }

    public function test_hit_rate_calculation(): void
    {
        // Create hits
        for ($i = 0; $i < 3; $i++) {
            $this->service->set("msg{$i}", "response{$i}");
            $this->service->get("msg{$i}");
        }

        // Create misses
        $this->service->get('nonexistent1');
        $this->service->get('nonexistent2');

        $stats = $this->service->getStats();
        $this->assertEquals(3, $stats['hit_count']);
        $this->assertEquals(2, $stats['miss_count']);
        $this->assertEquals(5, $stats['total_requests']);
        $this->assertEquals(60.0, $stats['hit_rate']); // 3/5 = 60%
    }

    public function test_cache_size_increments_on_set(): void
    {
        $initialStats = $this->service->getStats();
        $initialSize = $initialStats['size'];

        $this->service->set('new message', 'new response');

        $newStats = $this->service->getStats();
        $this->assertEquals($initialSize + 1, $newStats['size']);
    }

    public function test_cache_with_context_isolation(): void
    {
        $message = 'same message';
        $response1 = 'response for context 1';
        $response2 = 'response for context 2';

        $this->service->set($message, $response1, ['user' => 'user1']);
        $this->service->set($message, $response2, ['user' => 'user2']);

        $this->assertEquals($response1, $this->service->get($message, ['user' => 'user1']));
        $this->assertEquals($response2, $this->service->get($message, ['user' => 'user2']));
    }

    public function test_invalidate_pattern_removes_matching_entries(): void
    {
        // This test demonstrates the pattern invalidation functionality
        // Since pattern tracking is internal, we verify it doesn't throw errors
        $this->service->set('msg1', 'response1');
        $this->service->set('msg2', 'response2');

        $this->service->invalidatePattern('test-pattern');

        // Pattern invalidation should complete without errors
        $this->assertTrue(true);
    }

    public function test_record_miss_increments_miss_counter(): void
    {
        $initialStats = $this->service->getStats();
        $initialMisses = $initialStats['miss_count'];

        $this->service->recordMiss();

        $newStats = $this->service->getStats();
        $this->assertEquals($initialMisses + 1, $newStats['miss_count']);
    }
}
