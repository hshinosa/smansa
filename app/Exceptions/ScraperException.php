<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when Instagram scraper fails
 * Used for scraping operations and content processing
 */
class ScraperException extends Exception
{
    protected string $operation;
    protected array $context;

    /**
     * Create a new scraper exception
     *
     * @param string $operation The operation that failed
     * @param string $message Custom error message
     * @param array $context Additional context data
     * @param int $code Error code
     */
    public function __construct(
        string $operation = 'unknown',
        string $message = '',
        array $context = [],
        int $code = 500
    ) {
        $this->operation = $operation;
        $this->context = $context;

        if (empty($message)) {
            $message = "Terjadi kesalahan pada sistem scraper";
        }

        parent::__construct($message, $code);
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
     * Create exception for login failure
     */
    public static function loginFailed(?string $reason = null, array $context = []): self
    {
        $message = "Gagal login ke Instagram";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self('login', $message, $context, 401);
    }

    /**
     * Create exception for rate limit
     */
    public static function rateLimited(int $retryAfter = 3600): self
    {
        return new self(
            'rate_limit',
            "Instagram membatasi permintaan. Coba lagi dalam " . ceil($retryAfter / 60) . " menit.",
            ['retry_after' => $retryAfter],
            429
        );
    }

    /**
     * Create exception for profile not found
     */
    public static function profileNotFound(string $username): self
    {
        return new self(
            'profile_not_found',
            "Profil Instagram @{$username} tidak ditemukan",
            ['username' => $username],
            404
        );
    }

    /**
     * Create exception for connection error
     */
    public static function connectionFailed(?string $reason = null): self
    {
        $message = "Tidak dapat terhubung ke Instagram";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self('connection', $message, [], 503);
    }

    /**
     * Create exception for content processing failure
     */
    public static function processingFailed(string $postId, ?string $reason = null): self
    {
        $message = "Gagal memproses konten";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self('processing', $message, ['post_id' => $postId], 500);
    }

    /**
     * Create exception for bot account issue
     */
    public static function botAccountInactive(?string $reason = null): self
    {
        $message = "Akun bot tidak aktif";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self('bot_inactive', $message, [], 403);
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
                    'code' => 'SCRAPER_ERROR',
                    'message' => $this->getMessage(),
                    'details' => [
                        'operation' => $this->operation,
                    ]
                ]
            ], $this->code);
        }

        // For Inertia requests, return to previous page with error
        return redirect()->back()->with('error', $this->getMessage());
    }
}
