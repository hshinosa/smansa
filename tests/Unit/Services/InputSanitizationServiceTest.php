<?php

namespace Tests\Unit\Services;

use App\Services\InputSanitizationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InputSanitizationServiceTest extends TestCase
{
    protected InputSanitizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InputSanitizationService();
        Storage::fake('local');
    }

    public function test_sanitize_text_removes_html_tags(): void
    {
        $input = '<script>alert("xss")</script>Hello World';
        $result = $this->service->sanitizeText($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
    }

    public function test_sanitize_text_encodes_special_characters(): void
    {
        $input = 'Test <script> & "quotes"';
        $result = $this->service->sanitizeText($input);

        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
        $this->assertStringNotContainsString('&', $result);
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
    }

    public function test_sanitize_text_handles_empty_input(): void
    {
        $this->assertEquals('', $this->service->sanitizeText(''));
        $this->assertEquals('', $this->service->sanitizeText(null));
    }

    public function test_sanitize_text_removes_null_bytes(): void
    {
        $input = "Hello\0World";
        $result = $this->service->sanitizeText($input);

        $this->assertStringNotContainsString("\0", $result);
        $this->assertEquals('HelloWorld', $result);
    }

    public function test_sanitize_text_normalizes_whitespace(): void
    {
        $input = "Line 1\r\nLine 2\rLine 3";
        $result = $this->service->sanitizeText($input);

        $this->assertStringNotContainsString("\r\n", $result);
        $this->assertStringNotContainsString("\r", $result);
    }

    public function test_sanitize_text_allows_html_when_specified(): void
    {
        $input = '<p>Hello <strong>World</strong></p>';
        $result = $this->service->sanitizeText($input, true);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
    }

    public function test_sanitize_filename_removes_path_traversal(): void
    {
        $filename = '../../../etc/passwd';
        $result = $this->service->sanitizeFilename($filename);

        $this->assertStringNotContainsString('../', $result);
        $this->assertEquals('passwd', $result);
    }

    public function test_sanitize_filename_removes_null_bytes(): void
    {
        $filename = "file\0name.txt";
        $result = $this->service->sanitizeFilename($filename);

        $this->assertStringNotContainsString("\0", $result);
        $this->assertEquals('filename.txt', $result);
    }

    public function test_sanitize_filename_removes_control_characters(): void
    {
        $filename = "file\x01\x02name.txt";
        $result = $this->service->sanitizeFilename($filename);

        $this->assertEquals('filename.txt', $result);
    }

    public function test_sanitize_filename_removes_dangerous_characters(): void
    {
        $filename = 'file<>name|:?*.txt';
        $result = $this->service->sanitizeFilename($filename);

        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
        $this->assertStringNotContainsString('|', $result);
        $this->assertStringNotContainsString('?', $result);
        $this->assertStringNotContainsString('*', $result);
    }

    public function test_sanitize_filename_normalizes_dots(): void
    {
        $filename = 'file...name.txt';
        $result = $this->service->sanitizeFilename($filename);

        $this->assertStringNotContainsString('...', $result);
        $this->assertStringContainsString('.', $result);
    }

    public function test_sanitize_filename_removes_leading_dots(): void
    {
        $filename = '.hidden.file.txt';
        $result = $this->service->sanitizeFilename($filename);

        $this->assertStringNotContainsString('.hidden', $result);
        $this->assertStringContainsString('file.txt', $result);
    }

    public function test_sanitize_filename_limits_length(): void
    {
        $filename = str_repeat('a', 300) . '.txt';
        $result = $this->service->sanitizeFilename($filename);

        $this->assertLessThanOrEqual(255, strlen($result));
    }

    public function test_sanitize_array_recursively_sanitizes_strings(): void
    {
        $input = [
            'name' => '<script>alert("xss")</script>John',
            'email' => 'test@example.com',
            'nested' => [
                'bio' => '<p>Hello</p>',
                'age' => 25,
            ],
        ];

        $result = $this->service->sanitizeArray($input);

        $this->assertStringNotContainsString('<script>', $result['name']);
        $this->assertStringContainsString('&lt;script&gt;', $result['name']);
        $this->assertStringNotContainsString('<p>', $result['nested']['bio']);
        $this->assertEquals(25, $result['nested']['age']);
    }

    public function test_escape_search_wildcards_escapes_percent(): void
    {
        $search = 'test%value';
        $result = $this->service->escapeSearchWildcards($search);

        $this->assertEquals('test\%value', $result);
    }

    public function test_escape_search_wildcards_escapes_underscore(): void
    {
        $search = 'test_value';
        $result = $this->service->escapeSearchWildcards($search);

        $this->assertEquals('test\_value', $result);
    }

    public function test_is_safe_redirect_url_rejects_javascript_protocol(): void
    {
        $this->assertFalse($this->service->isSafeRedirectUrl('javascript:alert("xss")'));
    }

    public function test_is_safe_redirect_url_rejects_data_protocol(): void
    {
        $this->assertFalse($this->service->isSafeRedirectUrl('data:text/html,<script>alert("xss")</script>'));
    }

    public function test_is_safe_redirect_url_accepts_relative_urls(): void
    {
        $this->assertTrue($this->service->isSafeRedirectUrl('/dashboard'));
        $this->assertTrue($this->service->isSafeRedirectUrl('profile'));
    }

    public function test_is_safe_redirect_url_rejects_protocol_relative_urls(): void
    {
        $this->assertFalse($this->service->isSafeRedirectUrl('//evil.com'));
    }

    public function test_validate_and_sanitize_file_rejects_dangerous_extensions(): void
    {
        $file = UploadedFile::fake()->create('test.php', 100);

        $result = $this->service->validateAndSanitizeFile($file, 'document');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('tidak diizinkan', $result['error']);
    }

    public function test_validate_and_sanitize_file_rejects_invalid_image_type(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $result = $this->service->validateAndSanitizeFile($file, 'image');

        $this->assertTrue($result['valid']);
    }

    public function test_validate_and_sanitize_file_rejects_oversized_image(): void
    {
        $file = UploadedFile::fake()->create('large.jpg', 11000);

        $result = $this->service->validateAndSanitizeFile($file, 'image');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('terlalu besar', $result['error']);
    }

    public function test_validate_and_sanitize_file_accepts_valid_document(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $result = $this->service->validateAndSanitizeFile($file, 'document');

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_and_sanitize_file_accepts_valid_video(): void
    {
        $file = UploadedFile::fake()->create('video.mp4', 1000);

        $result = $this->service->validateAndSanitizeFile($file, 'video');

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_and_sanitize_file_returns_sanitized_name(): void
    {
        $file = UploadedFile::fake()->create('<script>evil.pdf', 100);

        $result = $this->service->validateAndSanitizeFile($file, 'document');

        $this->assertStringNotContainsString('<script>', $result['sanitized_name']);
    }
}
