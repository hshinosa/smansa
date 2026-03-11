<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Chat Response Cache Service with LRU Eviction
 * 
 * Optimizes AI responses by caching common queries to reduce:
 * - API costs
 * - Response latency
 * - Server load
 */
class ChatCacheService
{
    protected string $prefix = 'chat:cache:';
    protected int $ttl = 3600; // 1 hour default TTL
    protected int $maxSize = 500; // Max 500 cached responses (LRU)
    protected int $batchCount = 50; // Number of cache batches for efficient eviction

    /**
     * Generate cache key from message and context
     */
    public function generateKey(string $message, array $context = []): string
    {
        // Normalize message for consistent caching (case-insensitive hashing on macOS/Windows)
        $normalizedMessage = trim($message);
        
        // Include context in key to account for session-specific caching
        $content = $normalizedMessage . json_encode($context);
        
        // Use binary MD5 for case-sensitive hash across platforms
        $hash = hash('md5', $content);
        
        return $this->prefix . $hash;
    }
    
    /**
     * Generate session-specific cache key
     */
    public function generateSessionKey(string $message, string $sessionId): string
    {
        return $this->generateKey($message, ['session' => $sessionId]);
    }

    /**
     * Get cached response for a message
     */
    public function get(string $message, array $context = []): ?string
    {
        $key = $this->generateKey($message, $context);
        
        try {
            $cached = Cache::get($key);
            
            if ($cached !== null) {
                $this->recordHit();

                Log::info('[ChatCacheService] Cache hit', [
                    'message' => substr($message, 0, 50),
                    'key' => substr($key, -10),
                ]);

                return $cached;
            }

            $this->recordMiss();
            Log::info('[ChatCacheService] Cache miss', [
                'message' => substr($message, 0, 50),
                'key' => substr($key, -10),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::warning('[ChatCacheService] Get failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Set cached response for a message
     */
    public function set(string $message, string $response, array $context = []): void
    {
        $key = $this->generateKey($message, $context);
        
        try {
            // Check if cache is full and needs eviction
            $currentSize = Cache::get($this->prefix . 'size', 0);
            
            if ($currentSize >= $this->maxSize) {
                Log::info('[ChatCacheService] Cache full, evicting', [
                    'current_size' => $currentSize,
                    'max_size' => $this->maxSize,
                ]);
                
                $evicted = $this->evictBatch();
                
                if ($evicted > 0) {
                    Log::info('[ChatCacheService] Evicted cache entries', [
                        'count' => $evicted,
                        'new_size' => Cache::get($this->prefix . 'size', 0),
                    ]);
                }
            }

            // Store the response
            Cache::put($key, $response, now()->addSeconds($this->ttl));
            
            // Track cache metadata
            Cache::increment($this->prefix . 'size');
            Cache::put($this->prefix . 'last:' . $key, now()->timestamp, $this->ttl);

            // Add to batch for tracking
            $this->addToBatch($key);

            // Ensure size doesn't exceed max after eviction
            $newSize = (int) Cache::get($this->prefix . 'size', 0);
            if ($newSize > $this->maxSize) {
                $evicted = $this->evictBatch();
                if ($evicted > 0) {
                    Log::info('[ChatCacheService] Evicted cache entries post-insert', [
                        'count' => $evicted,
                        'new_size' => Cache::get($this->prefix . 'size', 0),
                    ]);
                }
            }
            
            Log::debug('[ChatCacheService] Response cached', [
                'message' => substr($message, 0, 50),
                'key' => substr($key, -10),
            ]);
            
        } catch (\Exception $e) {
            Log::error('[ChatCacheService] Set failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate specific cache entry
     */
    public function invalidate(string $message, array $context = []): void
    {
        $key = $this->generateKey($message, $context);
        
        try {
            Cache::forget($key);
            Cache::forget($this->prefix . 'last:' . $key);
            Cache::decrement($this->prefix . 'size');
            
            // Remove from batch
            $this->removeFromBatch($key);
            
            Log::info('[ChatCacheService] Cache invalidated', [
                'key' => substr($key, -10),
            ]);
        } catch (\Exception $e) {
            Log::warning('[ChatCacheService] Invalidate failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate cache by pattern (e.g., all documents in a category)
     */
    public function invalidatePattern(string $pattern): void
    {
        try {
            $patternKeys = Cache::get($this->prefix . 'patterns:' . $pattern, []);
            
            foreach ($patternKeys as $key) {
                Cache::forget($key);
                Cache::forget($this->prefix . 'last:' . $key);
                Cache::decrement($this->prefix . 'size');
                
                // Remove from all batches
                $this->removeFromBatch($key);
            }
            
            Cache::forget($this->prefix . 'patterns:' . $pattern);
            Cache::decrement($this->prefix . 'patterns_count');
            
            Log::info('[ChatCacheService] Pattern invalidation', [
                'pattern' => $pattern,
                'count' => count($patternKeys),
            ]);
        } catch (\Exception $e) {
            Log::error('[ChatCacheService] Pattern invalidation failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Evict oldest batch of cache entries (LRU strategy)
     */
    protected function evictBatch(): int
    {
        try {
            $oldestKey = null;
            $oldestTimestamp = PHP_INT_MAX;
            $oldestBatchKey = null;

            for ($i = 0; $i < $this->batchCount; $i++) {
                $batchKey = $this->prefix . 'batch:' . $i;
                $batch = Cache::get($batchKey, []);

                if (empty($batch)) {
                    continue;
                }

                foreach ($batch as $key) {
                    $timestamp = Cache::get($this->prefix . 'last:' . $key, 0);

                    if ($timestamp < $oldestTimestamp) {
                        $oldestTimestamp = $timestamp;
                        $oldestKey = $key;
                        $oldestBatchKey = $batchKey;
                    }
                }
            }

            if (!$oldestKey || !$oldestBatchKey) {
                return 0;
            }

            Cache::forget($oldestKey);
            Cache::forget($this->prefix . 'last:' . $oldestKey);
            Cache::decrement($this->prefix . 'size');

            $batch = Cache::get($oldestBatchKey, []);
            $batch = array_values(array_diff($batch, [$oldestKey]));

            if (empty($batch)) {
                Cache::forget($oldestBatchKey);
                Cache::decrement($this->prefix . 'batch_count');
            } else {
                Cache::put($oldestBatchKey, $batch, $this->ttl);
            }

            return 1;
        } catch (\Exception $e) {
            Log::error('[ChatCacheService] Batch eviction failed', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Add key to tracking batch
     */
    protected function addToBatch(string $key): void
    {
        try {
            // Find batch with space
            for ($i = 0; $i < $this->batchCount; $i++) {
                $batchKey = $this->prefix . 'batch:' . $i;
                $batch = Cache::get($batchKey, []);
                
                if (count($batch) < ($this->maxSize / $this->batchCount)) {
                    // Add to this batch
                    $batch[] = $key;
                    Cache::put($batchKey, $batch, $this->ttl);
                    return;
                }
            }
            
            // All batches full, create new batch
            Cache::put($this->prefix . 'batch:' . $this->batchCount, [$key], $this->ttl);
            Cache::increment($this->prefix . 'batch_count');
            
        } catch (\Exception $e) {
            Log::warning('[ChatCacheService] Add to batch failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove key from tracking batch
     */
    protected function removeFromBatch(string $key): void
    {
        try {
            for ($i = 0; $i < $this->batchCount; $i++) {
                $batchKey = $this->prefix . 'batch:' . $i;
                $batch = Cache::get($batchKey, []);
                
                $index = array_search($key, $batch, true);
                if ($index !== false) {
                    array_splice($batch, $index, 1);
                    
                    if (empty($batch)) {
                        Cache::forget($batchKey);
                        Cache::decrement($this->prefix . 'batch_count');
                    } else {
                        Cache::put($batchKey, $batch, $this->ttl);
                    }
                    
                    return;
                }
            }
        } catch (\Exception $e) {
            Log::warning('[ChatService] Remove from batch failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record cache hit for analytics
     */
    protected function recordHit(): void
    {
        Cache::increment($this->prefix . 'hits');
    }

    /**
     * Record cache miss for analytics
     */
    public function recordMiss(): void
    {
        Cache::increment($this->prefix . 'misses');
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $totalHits = (int) Cache::get($this->prefix . 'hits', 0);
        $totalMisses = (int) Cache::get($this->prefix . 'misses', 0);
        $totalRequests = $totalHits + $totalMisses;
        
        return [
            'size' => (int) Cache::get($this->prefix . 'size', 0),
            'max_size' => $this->maxSize,
            'ttl' => $this->ttl,
            'hit_count' => $totalHits,
            'miss_count' => $totalMisses,
            'total_requests' => $totalRequests,
            'hit_rate' => $totalRequests > 0 ? round(($totalHits / $totalRequests) * 100, 2) : 0.0,
            'batch_count' => (int) Cache::get($this->prefix . 'batch_count', 0),
        ];
    }

    /**
     * Clear all cache
     */
    public function clear(): void
    {
        try {
            // Get all cache keys
            for ($i = 0; $i < $this->batchCount; $i++) {
                $batch = Cache::get($this->prefix . 'batch:' . $i, []);
                
                foreach ($batch as $key) {
                    Cache::forget($key);
                    Cache::forget($this->prefix . 'last:' . $key);
                }
                
                Cache::forget($this->prefix . 'batch:' . $i);
            }
            
            // Clear counters
            Cache::forget($this->prefix . 'hits');
            Cache::forget($this->prefix . 'misses');
            Cache::forget($this->prefix . 'size');
            Cache::forget($this->prefix . 'batch_count');
            
            // Clear all pattern tracking
            $patterns = Cache::get($this->prefix . 'patterns_count', 0);
            for ($i = 0; $i < $patterns; $i++) {
                Cache::forget($this->prefix . 'pattern:' . $i);
            }
            Cache::forget($this->prefix . 'patterns_count');
            
            Log::info('[ChatCacheService] Cache cleared');
            
        } catch (\Exception $e) {
            Log::error('[ChatCacheService] Clear failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
