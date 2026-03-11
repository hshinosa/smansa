<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Security Headers Test Suite
 * 
 * Tests to verify that security headers are properly configured
 * and that server information is not leaked through HTTP headers.
 * 
 * @see SECURITY_HEADERS_GUIDE.md for complete documentation
 */
class SecurityHeadersTest extends TestCase
{
    #[Test]
    #[Group('security')]
    public function server_header_should_not_be_present()
    {
        $response = $this->get('/');
        
        $this->assertFalse(
            $response->headers->has('Server'),
            'Server header should not be present to prevent technology stack disclosure'
        );
    }
    
    #[Test]
    #[Group('security')]
    public function x_powered_by_header_should_not_be_present()
    {
        $response = $this->get('/');
        
        $this->assertFalse(
            $response->headers->has('X-Powered-By'),
            'X-Powered-By header should not be present to hide PHP version'
        );
    }
    
    #[Test]
    #[Group('security')]
    public function x_frame_options_header_should_be_set()
    {
        $response = $this->get('/');
        
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
    
    #[Test]
    #[Group('security')]
    public function x_content_type_options_header_should_be_set()
    {
        $response = $this->get('/');
        
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
    
    #[Test]
    #[Group('security')]
    public function strict_transport_security_header_should_be_set()
    {
        $response = $this->get('/');
        
        $this->assertTrue(
            $response->headers->has('Strict-Transport-Security'),
            'HSTS header should be present'
        );
        
        $hstsValue = $response->headers->get('Strict-Transport-Security');
        $this->assertStringContainsString('max-age=31536000', $hstsValue);
        $this->assertStringContainsString('includeSubDomains', $hstsValue);
    }
    
    #[Test]
    #[Group('security')]
    public function content_security_policy_header_should_be_set()
    {
        $response = $this->get('/');
        
        $this->assertTrue(
            $response->headers->has('Content-Security-Policy'),
            'CSP header should be present'
        );
        
        $cspValue = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $cspValue);
    }
    
    #[Test]
    #[Group('security')]
    public function referrer_policy_header_should_be_set()
    {
        $response = $this->get('/');
        
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
    
    #[Test]
    #[Group('security')]
    public function permissions_policy_header_should_be_set()
    {
        $response = $this->get('/');
        
        $this->assertTrue(
            $response->headers->has('Permissions-Policy'),
            'Permissions-Policy header should be present'
        );
        
        $permissionsValue = $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('geolocation=()', $permissionsValue);
    }
    
    #[Test]
    #[Group('security')]
    public function cross_origin_resource_policy_header_should_be_set()
    {
        $response = $this->get('/');
        
        $response->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
    }
    
    #[Test]
    #[Group('security')]
    public function cross_origin_opener_policy_header_should_be_set()
    {
        $response = $this->get('/');
        
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    }
    
    #[Test]
    #[Group('security')]
    #[Group('local')]
    public function security_headers_should_not_interfere_with_local_development()
    {
        if (!app()->environment('local')) {
            $this->markTestSkipped('This test only runs in local environment');
        }
        
        // In local environment, Server header removal should still work
        // but CSP might be relaxed
        $response = $this->get('/');
        
        $this->assertFalse(
            $response->headers->has('Server'),
            'Server header should be removed even in local environment'
        );
    }
    
    #[Test]
    #[Group('security')]
    public function error_pages_should_not_leak_server_information()
    {
        // Test 404 page
        $response = $this->get('/non-existent-page-that-does-not-exist');
        
        $this->assertFalse(
            $response->headers->has('Server'),
            'Server header should not be present on 404 pages'
        );
        
        $this->assertFalse(
            $response->headers->has('X-Powered-By'),
            'X-Powered-By header should not be present on 404 pages'
        );
    }
    
    #[Test]
    #[Group('security')]
    public function security_headers_should_be_consistent_across_all_pages()
    {
        $pages = [
            '/',
            '/kontak',
        ];
        
        foreach ($pages as $page) {
            $response = $this->get($page);
            
            $this->assertFalse(
                $response->headers->has('Server'),
                "Server header should not be present on {$page}"
            );
            
            $this->assertFalse(
                $response->headers->has('X-Powered-By'),
                "X-Powered-By header should not be present on {$page}"
            );
        }
    }
    
    #[Test]
    #[Group('security')]
    public function redirect_responses_should_not_leak_server_information()
    {
        // Create a simple redirect test
        $response = $this->get('/admin', ['HTTP_REFERER' => 'http://localhost']);
        
        $this->assertFalse(
            $response->headers->has('Server'),
            'Server header should not be present on redirects'
        );
    }
}
