<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $host = $request->getHost();
        $isDevHost = in_array($host, ['localhost', '127.0.0.1', '[::1]']);
        $isLocal = app()->environment('local');

        // SECURITY FIX: Remove/obfuscate Server header to prevent technology stack disclosure
        // This prevents attackers from easily identifying server infrastructure
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');
        
        // Option: Obfuscate instead of removing (uncomment to use)
        // $response->headers->set('Server', 'WebServer');

        // Dev/test/local host: jangan set CSP supaya Vite/inline script tidak diblokir saat pengembangan.
        if ($isLocal) {
            return $response;
        }

        $csp = [
            "default-src 'self' http: https: data: blob: 'unsafe-inline' *.sman1baleendah.sch.id *.hshinoshowcase.site",
            "img-src 'self' data: blob: https: http: *.sman1baleendah.sch.id *.hshinoshowcase.site",
            "font-src 'self' https://fonts.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com *.sman1baleendah.sch.id *.hshinoshowcase.site",
            "script-src 'self' 'unsafe-inline' https://static.cloudflareinsights.com *.sman1baleendah.sch.id *.hshinoshowcase.site",
            "script-src-elem 'self' 'unsafe-inline' https://static.cloudflareinsights.com *.sman1baleendah.sch.id *.hshinoshowcase.site",
            "script-src-attr 'self' 'unsafe-inline'",
            "connect-src 'self' https://cloudflareinsights.com *.sman1baleendah.sch.id *.hshinoshowcase.site",
            "frame-src 'self' https://www.google.com https://maps.google.com",
            "frame-ancestors 'self' *.sman1baleendah.sch.id *.hshinoshowcase.site",
            'upgrade-insecure-requests',
            "report-uri /api/security/csp-report",
        ];

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        
        // HSTS (HTTP Strict Transport Security)
        // Max-age: 1 year (31536000 seconds)
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        return $response;
    }
}
