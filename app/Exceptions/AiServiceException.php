<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when AI service fails
 * Used for Groq API, embedding service, and RAG operations
 */
class AiServiceException extends Exception
{
    protected string $service;
    protected string $operation;
    protected array $context;

    /**
     * Create a new AI service exception
     *
     * @param string $service The AI service name (groq, embedding, rag)
     * @param string $operation The operation that failed (chat, embed, search)
     * @param string $message Custom error message
     * @param array $context Additional context data
     * @param int $code Error code
     */
    public function __construct(
        string $service = 'ai',
        string $operation = 'unknown',
        string $message = '',
        array $context = [],
        int $code = 500
    ) {
        $this->service = $service;
        $this->operation = $operation;
        $this->context = $context;

        if (empty($message)) {
            $message = "Layanan AI tidak tersedia saat ini. Silakan coba lagi dalam beberapa saat.";
        }

        parent::__construct($message, $code);
    }

    /**
     * Get the service name
     */
    public function getService(): string
    {
        return $this->service;
    }

    /**
     * Get the operation
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Get the context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create exception for Groq API failure
     */
    public static function groqFailed(string $operation, ?string $reason = null, array $context = []): self
    {
        $message = "Gagal menghubungi layanan AI";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self('groq', $operation, $message, $context, 503);
    }

    /**
     * Create exception for embedding service failure
     */
    public static function embeddingFailed(?string $reason = null, array $context = []): self
    {
        $message = "Layanan embedding tidak tersedia";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self('embedding', 'create_embedding', $message, $context, 503);
    }

    /**
     * Create exception for RAG search failure
     */
    public static function ragSearchFailed(?string $reason = null, array $context = []): self
    {
        $message = "Pencarian informasi gagal";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self('rag', 'search', $message, $context, 500);
    }

    /**
     * Create exception for rate limit exceeded
     */
    public static function rateLimitExceeded(string $service = 'ai', int $retryAfter = 60): self
    {
        return new self(
            $service,
            'rate_limit',
            "Terlalu banyak permintaan. Silakan coba lagi dalam {$retryAfter} detik.",
            ['retry_after' => $retryAfter],
            429
        );
    }

    /**
     * Render the exception for HTTP response
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AI_SERVICE_ERROR',
                    'message' => $this->getMessage(),
                    'details' => [
                        'service' => $this->service,
                        'operation' => $this->operation,
                    ]
                ]
            ], $this->code);
        }

        // For Inertia requests, return to previous page with error
        return redirect()->back()->with('error', $this->getMessage());
    }
}
