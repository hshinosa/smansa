<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatHistory;
use App\Models\SiteSetting;
use App\Services\ChatCacheService;
use App\Services\RagService;
use App\Services\AiSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    protected RagService $ragService;

    public function __construct(
        RagService $ragService
    )
    {
        $this->ragService = $ragService;
    }

    /**
     * Send message to chatbot with streaming support
     */
    public function sendMessage(Request $request, ChatCacheService $cache)
    {
        $requestId = uniqid('chat_', true);
        $startTime = microtime(true);
        
        Log::info('[ChatController] ========== NEW CHAT REQUEST ==========', [
            'request_id' => $requestId,
            'timestamp' => now()->toIso8601String(),
        ]);

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:255',
            'stream' => 'nullable|boolean',
            'use_cache' => 'nullable|boolean',
        ]);

        // Validate and sanitize session_id
        $sessionId = $validated['session_id'] ?? null;
        
        // If no session_id or invalid format, create new one
        if (!$sessionId || !preg_match('/^session_\d+_[a-z0-9]+_\d+$/', $sessionId)) {
            $sessionId = 'session_' . time() . '_' . Str::random(12) . '_' . abs(crc32($request->ip() . $request->userAgent()));
        }
        
        $userMessage = $validated['message'];
        $useStreaming = $validated['stream'] ?? true;
        $useCache = $validated['use_cache'] ?? true;

        Log::info('[ChatController] Request validated', [
            'request_id' => $requestId,
            'session_id' => $sessionId,
            'user_message' => substr($userMessage, 0, 100) . (strlen($userMessage) > 100 ? '...' : ''),
            'use_streaming' => $useStreaming,
            'use_cache' => $useCache,
            'ip' => $request->ip(),
        ]);

        // Check cache first (only for non-streaming responses)
        if (!$useStreaming && $useCache) {
            $cached = $cache->get($userMessage);

            if ($cached !== null) {
                return response()->json([
                    'success' => true,
                    'message' => $cached,
                    'session_id' => $sessionId,
                    'provider' => 'cache',
                    'cached' => true,
                    'elapsed_ms' => 0,
                    'site_settings' => $this->getSiteSettings(),
                ]);
            }

            $cache->recordMiss();
        }

        // Clean old sessions (older than 24 hours) to prevent database bloat
        if (rand(1, 100) === 1) {
            ChatHistory::where('created_at', '<', now()->subHours(24))->delete();
        }

        // Save user message to history
        $userChatHistory = ChatHistory::create([
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'sender' => 'user',
            'message' => $userMessage,
            'metadata' => null,
            'is_rag_enhanced' => false,
        ]);

        // Get recent conversation history for context (excluding current message)
        $conversationHistory = ChatHistory::where('session_id', $sessionId)
            ->where('id', '!=', $userChatHistory->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn($chat) => [
                'sender' => $chat->sender,
                'message' => $chat->message,
            ])
            ->toArray();

        if ($useStreaming) {
            Log::info('[ChatController] Using streaming response', ['request_id' => $requestId]);
            return $this->streamResponse($sessionId, $userMessage, $conversationHistory, $request, $requestId);
        } else {
            Log::info('[ChatController] Using normal response', ['request_id' => $requestId]);
            return $this->normalResponse($sessionId, $userMessage, $conversationHistory, $request, $cache, $requestId);
        }
    }

    /**
     * Stream response using Server-Sent Events
     */
    protected function streamResponse(string $sessionId, string $userMessage, array $conversationHistory, Request $request, string $requestId = '')
    {
        return response()->stream(function () use ($sessionId, $userMessage, $conversationHistory, $request, $requestId) {
            $streamStartTime = microtime(true);
            
            Log::info('[ChatController] Stream started', [
                'request_id' => $requestId,
                'session_id' => $sessionId,
                'user_message' => substr($userMessage, 0, 50),
                'history_count' => count($conversationHistory),
            ]);
            
            // Start output buffering for streaming
            if (ob_get_level() == 0) ob_start();
            
            try {
                // Generate RAG response (conversation history already excludes current user message)
                Log::info('[ChatController] Calling RagService::generateRagResponse', [
                    'request_id' => $requestId,
                ]);
                
                $ragResponse = $this->ragService->generateRagResponse(
                    $userMessage,
                    $conversationHistory
                );
                
                $ragElapsed = round((microtime(true) - $streamStartTime) * 1000);
                
                Log::info('[ChatController] RagService response received', [
                    'request_id' => $requestId,
                    'success' => $ragResponse['success'] ?? 'not_set',
                    'is_rag_enhanced' => $ragResponse['is_rag_enhanced'] ?? false,
                    'message_length' => strlen($ragResponse['message'] ?? ''),
                    'message_preview' => substr($ragResponse['message'] ?? '', 0, 100),
                    'elapsed_ms' => $ragElapsed,
                ]);

                $fullMessage = $ragResponse['message'];
                $isRagEnhanced = $ragResponse['is_rag_enhanced'];

                // Helper function for safe flush
                $safeFlush = function() {
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    @flush();
                };

                // Send metadata first
                echo "data: " . json_encode([
                    'type' => 'metadata',
                    'session_id' => $sessionId,
                    'is_rag_enhanced' => $isRagEnhanced,
                ]) . "\n\n";
                $safeFlush();

                // Stream message word by word
                $words = preg_split('/(\s+)/', $fullMessage, -1, PREG_SPLIT_DELIM_CAPTURE);
                $buffer = '';
                
                foreach ($words as $word) {
                    $buffer .= $word;
                    
                    echo "data: " . json_encode([
                        'type' => 'content',
                        'content' => $word,
                    ]) . "\n\n";
                    
                    $safeFlush();
                    
                    usleep(30000); // 30ms delay between words for smooth streaming
                }

                // Send completion event
                echo "data: " . json_encode([
                    'type' => 'done',
                    'full_message' => $fullMessage,
                ]) . "\n\n";
                $safeFlush();
                
                $totalElapsed = round((microtime(true) - $streamStartTime) * 1000);
                Log::info('[ChatController] Stream completed', [
                    'request_id' => $requestId,
                    'total_elapsed_ms' => $totalElapsed,
                    'message_length' => strlen($fullMessage),
                ]);

                // Save bot response to history
                ChatHistory::create([
                    'session_id' => $sessionId,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'sender' => 'bot',
                    'message' => $fullMessage,
                    'metadata' => [
                        'model' => \App\Models\AiSetting::get('ai_model_name'),
                        'chunks_used' => count($ragResponse['context_chunks'] ?? []),
                    ],
                    'is_rag_enhanced' => $isRagEnhanced,
                    'retrieved_documents' => $ragResponse['retrieved_documents'] ?? null,
                ]);
                
            } catch (\Exception $e) {
                Log::error('[ChatController] Stream error', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Send error to client
                echo "data: " . json_encode([
                    'type' => 'error',
                    'message' => 'Terjadi kesalahan saat memproses pesan.',
                ]) . "\n\n";
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
            'Access-Control-Allow-Origin' => '*', // Allow CORS for streaming
        ]);
    }

    /**
     * Normal non-streaming response (fallback)
     */
    protected function normalResponse(string $sessionId, string $userMessage, array $conversationHistory, Request $request, ChatCacheService $cache, string $requestId = '')
    {
        $startTime = microtime(true);
        
        Log::info('[ChatController] Normal response started', [
            'request_id' => $requestId,
            'session_id' => $sessionId,
        ]);
        
        // Generate RAG response (conversation history already excludes current user message)
        $ragResponse = $this->ragService->generateRagResponse(
            $userMessage,
            $conversationHistory
        );

        $elapsed = (microtime(true) - $startTime) * 1000;
        
        Log::info('[ChatController] Normal response completed', [
            'request_id' => $requestId,
            'success' => $ragResponse['success'] ?? 'not_set',
            'message_length' => strlen($ragResponse['message'] ?? ''),
            'elapsed_ms' => round($elapsed),
        ]);

        // Save bot response to history
        $botChat = ChatHistory::create([
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'sender' => 'bot',
            'message' => $ragResponse['message'],
            'metadata' => [
                'model' => \App\Models\AiSetting::get('ai_model_name'),
                'chunks_used' => count($ragResponse['context_chunks'] ?? []),
                'elapsed_ms' => $elapsed,
            ],
            'is_rag_enhanced' => $ragResponse['is_rag_enhanced'],
            'retrieved_documents' => $ragResponse['retrieved_documents'] ?? null,
        ]);

        // Cache the response (if enabled)
        try {
            if ($cache) {
                $cache->set($userMessage, $ragResponse['message']);
            }
        } catch (\Exception $e) {
            Log::warning('[ChatController] Cache set failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'message' => $ragResponse['message'],
            'is_rag_enhanced' => $ragResponse['is_rag_enhanced'],
            'provider' => 'rag',
            'timestamp' => $botChat->created_at->toIso8601String(),
            'elapsed_ms' => $elapsed,
            'site_settings' => $this->getSiteSettings(),
        ]);
    }

    /**
     * Get chat history for a session
     */
    public function getHistory(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $history = ChatHistory::where('session_id', $validated['session_id'])
            ->orderBy('created_at', 'asc')
            ->limit($validated['limit'] ?? 50)
            ->get()
            ->map(fn($chat) => [
                'id' => $chat->id,
                'sender' => $chat->sender,
                'message' => $chat->message,
                'is_rag_enhanced' => $chat->is_rag_enhanced,
                'timestamp' => $chat->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Get cache statistics for monitoring
     */
    public function getCacheStats(ChatCacheService $cache)
    {
        return response()->json([
            'success' => true,
            'stats' => $cache->getStats(),
        ]);
    }

    /**
     * Clear chat cache
     */
    public function clearCache(ChatCacheService $cache)
    {
        $cache->clear();

        return response()->json([
            'success' => true,
            'message' => 'Chat cache cleared successfully',
        ]);
    }

    /**
     * Get site settings for frontend
     */
    protected function getSiteSettings(): array
    {
        return [
            'general' => [
                'whatsapp' => SiteSetting::get('whatsapp'),
                'phone' => SiteSetting::get('phone'),
                'site_name' => SiteSetting::get('site_name'),
            ],
        ];
    }
}
