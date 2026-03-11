<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for sanitizing and validating user inputs
 * Prevents XSS, SQL injection, path traversal, and file upload attacks
 */
class InputSanitizationService
{
    /**
     * Allowed file extensions for uploads
     */
    protected const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    protected const ALLOWED_VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogg'];
    protected const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
    
    /**
     * Maximum file sizes (in bytes)
     */
    protected const MAX_IMAGE_SIZE = 10485760; // 10MB
    protected const MAX_VIDEO_SIZE = 52428800; // 50MB
    protected const MAX_DOCUMENT_SIZE = 20971520; // 20MB
    
    /**
     * Dangerous file extensions that should never be allowed
     */
    protected const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phar',
        'exe', 'bat', 'cmd', 'sh', 'bash', 'zsh', 'csh',
        'jsp', 'jspx', 'war', 'jar',
        'py', 'rb', 'pl', 'cgi',
        'dll', 'so', 'dylib',
        'htaccess', 'htpasswd',
        'sql', 'sqlite', 'sqlite3',
        'js', 'vbs', 'wsf', 'ps1', 'psm1',
        'asa', 'cer', 'aspx', 'cshtml', 'vbhtml'
    ];

    /**
     * Sanitize text input for XSS prevention
     *
     * @param string|null $input
     * @param bool $allowHtml Whether to allow safe HTML
     * @return string
     */
    public function sanitizeText(?string $input, bool $allowHtml = false): string
    {
        if (empty($input)) {
            return '';
        }

        // Remove null bytes
        $input = str_replace("\0", '', $input);

        if (!$allowHtml) {
            // Strip all HTML tags for plain text
            $input = strip_tags($input);
            // Encode special characters
            $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Normalize whitespace (prevent header injection)
        $input = preg_replace('/[\r\n\x00]+/', ' ', $input);
        
        // Trim whitespace
        $input = trim($input);

        return $input;
    }

    /**
     * Sanitize filename to prevent path traversal attacks
     *
     * @param string $filename
     * @return string
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path components
        $filename = basename($filename);
        
        // Remove null bytes
        $filename = str_replace("\0", '', $filename);
        
        // Remove control characters
        $filename = preg_replace('/[\x00-\x1f\x7f]/', '', $filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[<>:"|?*]/', '', $filename);
        
        // Normalize dots (prevent .. traversal)
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Remove leading dots (hidden files)
        $filename = ltrim($filename, '.');
        
        // Limit length
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 250 - strlen($extension)) . '.' . $extension;
        }

        return $filename;
    }

    /**
     * Validate and sanitize uploaded file
     *
     * @param UploadedFile $file
     * @param string $type 'image', 'video', 'document'
     * @return array ['valid' => bool, 'error' => string|null, 'sanitized_name' => string]
     */
    public function validateAndSanitizeFile(UploadedFile $file, string $type = 'image'): array
    {
        $originalName = $file->getClientOriginalName();
        $sanitizedName = $this->sanitizeFilename($originalName);
        $extension = strtolower($file->getClientOriginalExtension());

        // Check for dangerous extensions
        if (in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
            Log::warning('Dangerous file upload attempted', [
                'original_name' => $originalName,
                'extension' => $extension,
                'ip' => request()->ip(),
            ]);
            return [
                'valid' => false,
                'error' => 'Tipe file tidak diizinkan untuk alasan keamanan.',
                'sanitized_name' => $sanitizedName,
            ];
        }

        // Get allowed extensions based on type
        $allowedExtensions = match($type) {
            'image' => self::ALLOWED_IMAGE_EXTENSIONS,
            'video' => self::ALLOWED_VIDEO_EXTENSIONS,
            'document' => self::ALLOWED_DOCUMENT_EXTENSIONS,
            default => self::ALLOWED_IMAGE_EXTENSIONS,
        };

        if (!in_array($extension, $allowedExtensions, true)) {
            return [
                'valid' => false,
                'error' => 'Tipe file tidak didukung. Tipe yang diizinkan: ' . implode(', ', $allowedExtensions),
                'sanitized_name' => $sanitizedName,
            ];
        }

        // Validate MIME type matches extension
        if (!$this->validateMimeType($file, $extension)) {
            Log::warning('MIME type mismatch detected', [
                'original_name' => $originalName,
                'extension' => $extension,
                'mime_type' => $file->getMimeType(),
            ]);
            return [
                'valid' => false,
                'error' => 'Tipe file tidak sesuai dengan ekstensi.',
                'sanitized_name' => $sanitizedName,
            ];
        }

        // Check file size
        $maxSize = match($type) {
            'image' => self::MAX_IMAGE_SIZE,
            'video' => self::MAX_VIDEO_SIZE,
            'document' => self::MAX_DOCUMENT_SIZE,
            default => self::MAX_IMAGE_SIZE,
        };

        if ($file->getSize() > $maxSize) {
            return [
                'valid' => false,
                'error' => 'Ukuran file terlalu besar. Maksimum: ' . $this->formatBytes($maxSize),
                'sanitized_name' => $sanitizedName,
            ];
        }

        // Verify image files are valid images
        if ($type === 'image' && !$this->isValidImage($file)) {
            return [
                'valid' => false,
                'error' => 'File gambar tidak valid atau rusak.',
                'sanitized_name' => $sanitizedName,
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'sanitized_name' => $sanitizedName,
        ];
    }

    /**
     * Sanitize array of inputs recursively
     *
     * @param array $inputs
     * @param bool $allowHtml
     * @return array
     */
    public function sanitizeArray(array $inputs, bool $allowHtml = false): array
    {
        $sanitized = [];
        
        foreach ($inputs as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $allowHtml);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeText($value, $allowHtml);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Escape SQL-like wildcards for LIKE queries
     *
     * @param string $search
     * @return string
     */
    public function escapeSearchWildcards(string $search): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $search);
    }

    /**
     * Validate URL to prevent open redirect vulnerabilities
     *
     * @param string $url
     * @param array $allowedHosts
     * @return bool
     */
    public function isSafeRedirectUrl(string $url, array $allowedHosts = []): bool
    {
        // Reject javascript: and data: URLs
        if (preg_match('/^(javascript|data|vbscript|file):/i', $url)) {
            return false;
        }

        // Parse URL
        $parsed = parse_url($url);
        
        // Relative URLs (no host) are generally safe
        if (empty($parsed['host'])) {
            // But check for protocol-relative URLs
            if (str_starts_with($url, '//')) {
                return false;
            }
            return true;
        }

        // Check against allowed hosts
        if (!empty($allowedHosts)) {
            return in_array($parsed['host'], $allowedHosts, true);
        }

        // Only allow same-domain redirects by default
        $requestHost = request()->getHost();
        return $parsed['host'] === $requestHost;
    }

    /**
     * Validate MIME type matches file extension
     *
     * @param UploadedFile $file
     * @param string $extension
     * @return bool
     */
    protected function validateMimeType(UploadedFile $file, string $extension): bool
    {
        $mimeType = $file->getMimeType();
        
        $expectedMimeTypes = match($extension) {
            'jpg', 'jpeg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'mp4' => ['video/mp4'],
            'webm' => ['video/webm'],
            'pdf' => ['application/pdf'],
            default => [],
        };

        if (empty($expectedMimeTypes)) {
            return true; // Unknown extension, let other validation handle it
        }

        return in_array($mimeType, $expectedMimeTypes, true);
    }

    /**
     * Verify image file is a valid image
     *
     * @param UploadedFile $file
     * @return bool
     */
    protected function isValidImage(UploadedFile $file): bool
    {
        try {
            $imageInfo = @getimagesize($file->getRealPath());
            return $imageInfo !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Format bytes to human readable
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
