<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cookie Security Middleware
 * 
 * Enforces comprehensive cookie security policies:
 * - HttpOnly flag to prevent XSS attacks
 * - Secure flag for HTTPS-only transmission
 * - SameSite attribute for CSRF protection
 * - Cookie prefix validation (__Secure-, __Host-)
 * 
 * @package App\Http\Middleware
 */
class CookieSecurity
{
    /**
     * Cookies that should always have strict security settings
     */
    protected array $strictCookies = [
        'laravel_session',
        'XSRF-TOKEN',
        'remember_web',
    ];

    /**
     * Cookies that require the Secure flag (HTTPS only)
     */
    protected array $secureCookies = [
        'laravel_session',
        'XSRF-TOKEN',
        'remember_web',
    ];

    /**
     * Cookies that require the HttpOnly flag
     */
    protected array $httpOnlyCookies = [
        'laravel_session',
        'remember_web',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Apply security attributes to all cookies
        $this->secureCookies($response);

        // Validate cookie prefixes
        $this->validateCookiePrefixes($response);

        // Log security violations if any
        $this->logSecurityViolations($response);

        return $response;
    }

    /**
     * Apply security attributes to response cookies
     */
    protected function secureCookies(Response $response): void
    {
        $cookies = $response->headers->getCookies();
        
        foreach ($cookies as $cookie) {
            $name = $cookie->getName();
            
            // Determine security attributes based on environment and cookie name
            $secure = $this->shouldBeSecure($name);
            $httpOnly = $this->shouldBeHttpOnly($name);
            $sameSite = $this->getSameSiteValue($name);

            // Create new cookie with security attributes
            $newCookie = Cookie::create(
                $name,
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $secure,
                $httpOnly,
                false, // raw
                $sameSite
            );

            // Replace the cookie
            $response->headers->removeCookie($name, $cookie->getPath(), $cookie->getDomain());
            $response->headers->setCookie($newCookie);
        }
    }

    /**
     * Determine if cookie should have Secure flag
     */
    protected function shouldBeSecure(string $cookieName): bool
    {
        // Always secure in production
        if (app()->environment('production')) {
            return true;
        }

        // Secure specific cookies based on configuration
        if (in_array($cookieName, $this->secureCookies)) {
            return config('session.secure', false);
        }

        // Default to environment setting
        return config('session.secure', false);
    }

    /**
     * Determine if cookie should have HttpOnly flag
     */
    protected function shouldBeHttpOnly(string $cookieName): bool
    {
        // XSRF-TOKEN must NOT be HttpOnly (needs to be read by JavaScript)
        if ($cookieName === 'XSRF-TOKEN') {
            return false;
        }

        // Check if cookie is in the HttpOnly list
        if (in_array($cookieName, $this->httpOnlyCookies)) {
            return true;
        }

        // Default to true for security
        return config('session.http_only', true);
    }

    /**
     * Get SameSite value for cookie
     */
    protected function getSameSiteValue(string $cookieName): ?string
    {
        // Strict for authentication cookies
        if (in_array($cookieName, $this->strictCookies)) {
            return app()->environment('production') ? 'strict' : 'lax';
        }

        // Get from configuration
        return config('session.same_site', 'lax');
    }

    /**
     * Validate cookie prefixes according to RFC 6265bis
     */
    protected function validateCookiePrefixes(Response $response): void
    {
        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            $name = $cookie->getName();

            // __Secure- prefix validation
            if (str_starts_with($name, '__Secure-')) {
                if (!$cookie->isSecure()) {
                    Log::warning("Cookie with __Secure- prefix must have Secure flag", [
                        'cookie' => $name,
                        'url' => request()->fullUrl()
                    ]);
                }
            }

            // __Host- prefix validation
            if (str_starts_with($name, '__Host-')) {
                if (!$cookie->isSecure() || $cookie->getDomain() !== null || $cookie->getPath() !== '/') {
                    Log::warning("Cookie with __Host- prefix violates RFC 6265bis requirements", [
                        'cookie' => $name,
                        'secure' => $cookie->isSecure(),
                        'domain' => $cookie->getDomain(),
                        'path' => $cookie->getPath(),
                        'url' => request()->fullUrl()
                    ]);
                }
            }
        }
    }

    /**
     * Log security violations
     */
    protected function logSecurityViolations(Response $response): void
    {
        if (!config('app.debug')) {
            return;
        }

        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            $violations = [];

            if (!$cookie->isHttpOnly() && $cookie->getName() !== 'XSRF-TOKEN') {
                $violations[] = 'Missing HttpOnly flag';
            }

            if (!$cookie->isSecure() && app()->environment('production')) {
                $violations[] = 'Missing Secure flag in production';
            }

            if ($cookie->getSameSite() === null) {
                $violations[] = 'Missing SameSite attribute';
            }

            if (!empty($violations)) {
                Log::debug("Cookie security violations detected", [
                    'cookie' => $cookie->getName(),
                    'violations' => $violations,
                    'url' => request()->fullUrl()
                ]);
            }
        }
    }
}
