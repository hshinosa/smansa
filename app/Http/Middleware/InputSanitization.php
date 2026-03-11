<?php

namespace App\Http\Middleware;

use App\Services\InputSanitizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for sanitizing all user inputs
 * Prevents XSS, SQL injection, and other injection attacks
 */
class InputSanitization
{
    protected InputSanitizationService $sanitizer;

    public function __construct(InputSanitizationService $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip sanitization for specific routes that need raw input
        if ($this->shouldSkipSanitization($request)) {
            return $next($request);
        }

        // Sanitize input parameters
        $this->sanitizeInputs($request);

        return $next($request);
    }

    /**
     * Determine if sanitization should be skipped for this request
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldSkipSanitization(Request $request): bool
    {
        $skipRoutes = [
            'api/chat/send',  // Chat messages handled separately with RAG
            'api/security/csp-report', // CSP reports are JSON
            'admin/ai-settings', // AI settings contain JSON API keys
        ];

        foreach ($skipRoutes as $route) {
            if ($request->is($route) || $request->is('*/' . $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize request inputs
     *
     * @param Request $request
     * @return void
     */
    protected function sanitizeInputs(Request $request): void
    {
        $input = $request->all();

        // Fields that should allow HTML (rich text editors)
        $htmlAllowedFields = [
            'content',
            'description',
            'answer',
            'welcome_text_html',
            'description_html',
            'announcement_text',
            'contact_info',
            'bio',
            'testimonial',
        ];

        $sanitized = [];
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, in_array($key, $htmlAllowedFields, true));
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizer->sanitizeText($value, in_array($key, $htmlAllowedFields, true));
            } else {
                $sanitized[$key] = $value;
            }
        }

        // Replace request inputs with sanitized versions
        $request->replace($sanitized);
    }

    /**
     * Recursively sanitize array values
     *
     * @param array $array
     * @param bool $allowHtml
     * @return array
     */
    protected function sanitizeArray(array $array, bool $allowHtml = false): array
    {
        $sanitized = [];
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $allowHtml);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizer->sanitizeText($value, $allowHtml);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}
