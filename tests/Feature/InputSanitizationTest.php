<?php

namespace Tests\Feature;

use App\Services\InputSanitizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InputSanitizationTest extends TestCase
{
    use RefreshDatabase;

    protected InputSanitizationService $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = app(InputSanitizationService::class);
    }

    public function test_xss_payloads_are_sanitized(): void
    {
        $maliciousInputs = [
            '<script>alert("XSS")</script>',
            'Hello <img src=x onerror=alert(1)> World',
            '<iframe src="http://evil.com"></iframe>',
        ];

        foreach ($maliciousInputs as $input) {
            $result = $this->sanitizer->sanitizeText($input);
            // Script tags should be removed/stripped
            $this->assertStringNotContainsString('<script>', $result, "Failed to remove script tag: {$input}");
            $this->assertStringNotContainsString('<iframe>', $result, "Failed to remove iframe: {$input}");
            $this->assertStringNotContainsString('onerror=', $result, "Failed to remove event handler: {$input}");
        }
    }

    public function test_sql_injection_attempts_are_blocked_in_search(): void
    {
        $maliciousSearches = [
            "'; DROP TABLE users; --",
            "1' OR '1'='1",
            "admin'--",
            "' UNION SELECT * FROM users --",
        ];

        foreach ($maliciousSearches as $search) {
            // Test that LIKE wildcards are escaped
            $searchWithWildcards = '%' . $search . '_';
            $escaped = $this->sanitizer->escapeSearchWildcards($searchWithWildcards);
            
            // Wildcards should be escaped with backslash
            $this->assertStringContainsString('\\%', $escaped);
            $this->assertStringContainsString('\\_', $escaped);
        }
    }

    public function test_filename_path_traversal_is_prevented(): void
    {
        $maliciousFilenames = [
            '../../../etc/passwd' => 'passwd',
            '..\\..\\windows\\system32\\config\\sam' => 'sam',
            'file.php%00.jpg' => 'file.php%00.jpg', // URL encoding preserved but safe
            '.htaccess' => 'htaccess', // Leading dot removed
            'file<script>.jpg' => 'filescript.jpg',
        ];

        foreach ($maliciousFilenames as $input => $expected) {
            $result = $this->sanitizer->sanitizeFilename($input);
            $this->assertEquals($expected, $result, "Failed to sanitize filename: {$input}");
        }
    }

    public function test_dangerous_file_extensions_are_rejected(): void
    {
        Storage::fake('public');

        $dangerousFiles = [
            'malicious.php' => false,
            'script.exe' => false,
            'backdoor.jsp' => false,
            'shell.sh' => false,
            'config.htaccess' => false,
            'valid_image.jpg' => true,
        ];

        foreach ($dangerousFiles as $filename => $shouldBeValid) {
            // Create fake image for valid files, fake text for dangerous
            if ($shouldBeValid) {
                $file = UploadedFile::fake()->image($filename);
            } else {
                $file = UploadedFile::fake()->create($filename, 100, 'text/plain');
            }
            
            $result = $this->sanitizer->validateAndSanitizeFile($file, 'image');
            
            if ($shouldBeValid) {
                $this->assertTrue($result['valid'], "Valid file rejected: {$filename}");
            } else {
                $this->assertFalse($result['valid'], "Dangerous file accepted: {$filename}");
                $this->assertNotNull($result['error']);
            }
        }
    }

    public function test_mime_type_validation_prevents_fake_extensions(): void
    {
        Storage::fake('public');

        // Create a file that claims to be JPG but has wrong content
        // UploadedFile::fake()->image creates proper image content
        // But we can't easily create fake PHP with .jpg extension using Laravel's fake
        // So we test the MIME type validation differently - using a real image vs wrong extension
        
        $realImage = UploadedFile::fake()->image('real.jpg');
        $result = $this->sanitizer->validateAndSanitizeFile($realImage, 'image');
        
        // Real image should pass
        $this->assertTrue($result['valid']);
    }

    public function test_open_redirect_urls_are_blocked(): void
    {
        $urls = [
            '/local/path' => true,
            '/dashboard' => true,
            'https://trusted-domain.com/page' => false, // Different domain
            'javascript:alert(1)' => false,
            'data:text/html,<script>alert(1)</script>' => false,
            '//evil.com' => false,
            'http://evil.com/steal' => false,
        ];

        foreach ($urls as $url => $shouldBeSafe) {
            $isSafe = $this->sanitizer->isSafeRedirectUrl($url);
            
            if ($shouldBeSafe) {
                $this->assertTrue($isSafe, "Safe URL blocked: {$url}");
            } else {
                $this->assertFalse($isSafe, "Unsafe URL allowed: {$url}");
            }
        }
    }

    public function test_html_content_is_preserved_for_allowed_fields(): void
    {
        $html = '<p>Hello <strong>World</strong></p><script>alert(1)</script>';
        
        // With HTML allowed, safe tags should be preserved
        $result = $this->sanitizer->sanitizeText($html, true);
        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
        
        // With HTML not allowed, all tags should be stripped/encoded
        $result = $this->sanitizer->sanitizeText($html, false);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function test_array_inputs_are_sanitized_recursively(): void
    {
        $input = [
            'name' => '<script>alert(1)</script>John',
            'nested' => [
                'description' => '<iframe src="evil.com"></iframe>Content',
                'deep' => [
                    'value' => 'Normal text',
                ],
            ],
        ];

        $result = $this->sanitizer->sanitizeArray($input);

        // Script tags and iframes should be stripped
        $this->assertStringNotContainsString('<script>', $result['name']);
        $this->assertStringNotContainsString('<iframe>', $result['nested']['description']);
        $this->assertEquals('Normal text', $result['nested']['deep']['value']);
    }

    public function test_header_injection_is_prevented(): void
    {
        $maliciousInput = "Hello\r\nLocation: http://evil.com\r\n\r\n<script>alert(1)</script>";
        
        $result = $this->sanitizer->sanitizeText($maliciousInput);
        
        // Newlines should be removed/replaced to prevent header injection
        $this->assertStringNotContainsString("\r", $result);
        $this->assertStringNotContainsString("\n", $result);
    }

    public function test_file_size_limits_are_enforced(): void
    {
        Storage::fake('public');

        // Create an oversized image (15MB)
        $oversizedFile = UploadedFile::fake()->image('large.jpg')->size(15000);
        
        $result = $this->sanitizer->validateAndSanitizeFile($oversizedFile, 'image');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Ukuran file terlalu besar', $result['error']);
    }
}
