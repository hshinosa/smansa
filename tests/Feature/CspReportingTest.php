<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CspReportingTest extends TestCase
{
    /**
     * Test that the CSP reporting endpoint receives and logs data.
     */
    public function test_csp_report_endpoint_logs_violation()
    {
        // Spy on the logger instead of mocking strictly to allow other logs (like errors) to pass through if needed,
        // or just ensure we catch the warning. 
        // Better: Partial mock.
        Log::shouldReceive('channel')->with('daily')->andReturnSelf();
        Log::shouldReceive('warning')->once()->withArgs(function ($message, $context) {
            return $message === 'CSP Violation Reported:' &&
                   isset($context['report']['csp-report']['blocked-uri']);
        });
        
        // Allow other logs to prevent "no expectation specified" error if exception handler logs something
        Log::shouldReceive('error')->andReturnNull();
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('debug')->andReturnNull();

        // Sample CSP report payload (standard browser format)
        $payload = [
            'csp-report' => [
                'document-uri' => 'http://localhost/',
                'referrer' => '',
                'violated-directive' => 'script-src-elem',
                'effective-directive' => 'script-src-elem',
                'original-policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://static.cloudflareinsights.com;",
                'disposition' => 'enforce',
                'blocked-uri' => 'http://evil.com/script.js',
                'line-number' => 1,
                'column-number' => 1,
                'source-file' => 'http://localhost/',
                'status-code' => 200,
                'script-sample' => ''
            ]
        ];

        // Send POST request to the reporting endpoint
        // CSP reports often send Content-Type: application/csp-report, but Laravel handles JSON well.
        // We'll test with standard JSON request which our controller supports.
        $response = $this->postJson('/api/security/csp-report', $payload);

        // Assert response is 204 No Content (standard for report endpoints) or 200 OK
        // Our controller returns 204.
        $response->assertStatus(204);
    }

    /**
     * Test that the endpoint handles empty or malformed payloads gracefully.
     */
    public function test_csp_report_endpoint_handles_empty_payload()
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning')->once(); 
        // Allow other logs
        Log::shouldReceive('error')->andReturnNull();
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('debug')->andReturnNull();

        $response = $this->postJson('/api/security/csp-report', []);

        $response->assertStatus(204);
    }

    /**
     * Test that the CSP reporting endpoint rejects large payloads.
     */
    public function test_csp_report_endpoint_rejects_large_payload()
    {
        // Generate a large payload (> 8KB)
        $largeData = str_repeat('a', 9000);
        
        $response = $this->postJson('/api/security/csp-report', [
            'data' => $largeData
        ]);

        $response->assertStatus(413);
    }

    /**
     * Test that the CSP reporting endpoint is rate limited.
     */
    public function test_csp_report_endpoint_rate_limiting()
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning')->andReturnNull();
        Log::shouldReceive('error')->andReturnNull();
        Log::shouldReceive('info')->andReturnNull();

        // Send 5 requests (within limit)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/security/csp-report', ['test' => $i])
                 ->assertStatus(204);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/security/csp-report', ['test' => 'limit']);
        $response->assertStatus(429);
    }
}
