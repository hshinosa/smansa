<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when content is not found
 * Used for posts, pages, and other content types
 */
class ContentNotFoundException extends Exception
{
    protected string $contentType;
    protected mixed $identifier;

    /**
     * Create a new content not found exception
     *
     * @param string $contentType Type of content (post, page, gallery, etc.)
     * @param mixed $identifier The identifier used to search (id, slug, etc.)
     * @param string $message Custom error message
     * @param int $code Error code
     */
    public function __construct(
        string $contentType = 'content',
        mixed $identifier = null,
        string $message = '',
        int $code = 404
    ) {
        $this->contentType = $contentType;
        $this->identifier = $identifier;

        if (empty($message)) {
            $message = ucfirst($contentType) . ' tidak ditemukan';
            if ($identifier) {
                $message .= " dengan identifier: {$identifier}";
            }
        }

        parent::__construct($message, $code);
    }

    /**
     * Get the content type
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Get the identifier
     */
    public function getIdentifier(): mixed
    {
        return $this->identifier;
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
                    'code' => 'CONTENT_NOT_FOUND',
                    'message' => $this->getMessage(),
                    'details' => [
                        'content_type' => $this->contentType,
                        'identifier' => $this->identifier,
                    ]
                ]
            ], 404);
        }

        // For Inertia requests, return to previous page with error
        return redirect()->back()->with('error', $this->getMessage());
    }
}
