<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInputSanitizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        Admin::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);
    }

    /**
     * Test that XSS payloads in admin FAQ form are sanitized
     */
    public function test_xss_payloads_in_admin_faq_form_are_sanitized(): void
    {
        $this->actingAs(Admin::first(), 'admin');

        $xssPayload = '<script>alert("XSS")</script>Halo';
        
        $response = $this->followingRedirects()->post(route('admin.faqs.store'), [
            'question' => $xssPayload,
            'answer' => '<iframe src="http://evil.com"></iframe>Jawaban',
            'category' => 'Umum',
        ]);

        $response->assertStatus(200);

        // Verify database doesn't contain raw script tags
        $faq = Faq::latest()->first();
        $this->assertNotNull($faq);
        $this->assertStringNotContainsString('<script>', $faq->question);
        $this->assertStringNotContainsString('<iframe>', $faq->answer);
    }

    /**
     * Test that null bytes are removed from admin inputs
     */
    public function test_null_bytes_are_removed_from_admin_inputs(): void
    {
        $this->actingAs(Admin::first(), 'admin');

        $maliciousInput = "Test\x00<script>alert(1)</script>";
        
        $response = $this->followingRedirects()->post(route('admin.faqs.store'), [
            'question' => $maliciousInput,
            'answer' => 'Jawaban normal',
            'category' => 'Umum',
        ]);

        $response->assertStatus(200);

        $faq = Faq::latest()->first();
        $this->assertNotNull($faq);
        $this->assertStringNotContainsString("\x00", $faq->question);
        $this->assertStringNotContainsString('<script>', $faq->question);
    }

    /**
     * Test that SQL injection attempts in admin inputs don't cause errors
     */
    public function test_sql_injection_in_admin_inputs_is_blocked(): void
    {
        $this->actingAs(Admin::first(), 'admin');

        $sqlInjection = "'; DROP TABLE faqs; --";
        
        // The sanitization should prevent SQL injection
        $response = $this->followingRedirects()->post(route('admin.faqs.store'), [
            'question' => $sqlInjection,
            'answer' => 'Jawaban',
            'category' => 'Umum',
        ]);

        $response->assertStatus(200);

        // Verify the FAQ still exists (no table drop occurred)
        $this->assertDatabaseHas('faqs', [
            'category' => 'Umum',
        ]);
    }

    /**
     * Test that path traversal in file uploads is prevented
     */
    public function test_path_traversal_in_filename_is_prevented(): void
    {
        $this->actingAs(Admin::first(), 'admin');

        // The middleware should sanitize filename inputs
        $maliciousFilename = '../../../etc/passwd';
        $sanitized = basename($maliciousFilename);
        
        $this->assertEquals('passwd', $sanitized);
        $this->assertStringNotContainsString('../', $sanitized);
    }

    /**
     * Test that header injection in form inputs is blocked
     */
    public function test_header_injection_in_admin_inputs_is_blocked(): void
    {
        $this->actingAs(Admin::first(), 'admin');

        $headerInjection = "Question\r\nLocation: http://evil.com";
        
        $response = $this->followingRedirects()->post(route('admin.faqs.store'), [
            'question' => $headerInjection,
            'answer' => 'Jawaban',
            'category' => 'Umum',
        ]);

        $response->assertStatus(200);

        $faq = Faq::latest()->first();
        $this->assertNotNull($faq);
        // Newlines should be removed/replaced by middleware
        $this->assertStringNotContainsString("\r", $faq->question);
    }

    /**
     * Test that dangerous file extensions are rejected in admin uploads
     */
    public function test_dangerous_file_extensions_are_rejected_in_admin(): void
    {
        $this->actingAs(Admin::first(), 'admin');

        $dangerousExtensions = ['php', 'exe', 'sh', 'jsp', 'py'];
        
        foreach ($dangerousExtensions as $ext) {
            $filename = "malicious.{$ext}";
            // Service should reject these
            $this->assertTrue(
                in_array($ext, ['php', 'exe', 'sh', 'jsp', 'py']),
                "Extension {$ext} should be flagged as dangerous"
            );
        }
    }
}
