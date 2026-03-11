<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PerformanceOptimization
{
    /**
     * Handle an incoming request and add performance optimization headers
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Add caching headers for static assets
        if ($this->isStaticAsset($request)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        }
        
        // Add compression headers
        if (!$response->headers->has('Content-Encoding')) {
            $response->headers->set('Vary', 'Accept-Encoding');
        }
        
        // Add resource hints
        $response->headers->set('Link', $this->getResourceHints(), false);
        
        // Add timing headers for monitoring
        if (config('app.debug')) {
            $response->headers->set('Server-Timing', $this->getServerTiming());
        }
        
        return $response;
    }
    
    /**
     * Check if request is for static asset
     */
    private function isStaticAsset(Request $request): bool
    {
        $path = $request->path();
        $extensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'woff', 'woff2', 'ttf', 'eot', 'ico'];
        
        foreach ($extensions as $ext) {
            if (str_ends_with($path, '.' . $ext)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get resource hints for critical resources
     */
    private function getResourceHints(): string
    {
        $hints = [];
        
        // Preconnect to external domains
        $hints[] = '<https://fonts.googleapis.com>; rel=preconnect';
        $hints[] = '<https://fonts.gstatic.com>; rel=preconnect; crossorigin';
        
        // DNS prefetch for external resources
        $hints[] = '<https://cdnjs.cloudflare.com>; rel=dns-prefetch';
        
        return implode(', ', $hints);
    }
    
    /**
     * Get server timing metrics
     */
    private function getServerTiming(): string
    {
        if (defined('LARAVEL_START')) {
            $duration = (microtime(true) - LARAVEL_START) * 1000;
            return sprintf('total;dur=%.2f', $duration);
        }
        
        return '';
    }
}
