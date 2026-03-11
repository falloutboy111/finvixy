<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

/**
 * Validates and sanitizes uploaded images and documents.
 * Checks file size, MIME type, dimensions, and attempts basic malware detection.
 */
class UploadValidatorService
{
    // File size limits
    private const MAX_IMAGE_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB
    private const MAX_PDF_SIZE_BYTES = 50 * 1024 * 1024; // 50 MB
    private const MIN_FILE_SIZE_BYTES = 100;

    // Allowed MIME types
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const ALLOWED_DOCUMENT_TYPES = [
        'application/pdf',
    ];

    // Image dimension limits
    private const MIN_DIMENSION = 100;
    private const MAX_DIMENSION = 10000;

    /**
     * Validate an uploaded image file.
     *
     * @param UploadedFile|string $file File object or path to file contents
     *
     * @return array{valid: bool, error: string|null, warnings: array}
     */
    public function validateImage($file): array
    {
        $errors = [];
        $warnings = [];

        $fileContents = $this->getFileContents($file);
        $mimeType = $this->getMimeType($file, $fileContents);
        $fileSize = strlen($fileContents);

        // Check MIME type
        if (! in_array($mimeType, self::ALLOWED_IMAGE_TYPES)) {
            $errors[] = "Invalid image format. Allowed: JPEG, PNG, WEBP. Got: $mimeType";
        }

        // Check file size
        if ($fileSize < self::MIN_FILE_SIZE_BYTES) {
            $errors[] = "File is too small (possibly corrupted)";
        }

        if ($fileSize > self::MAX_IMAGE_SIZE_BYTES) {
            $errors[] = "Image exceeds maximum size of 10 MB. Size: " . $this->formatBytes($fileSize);
        }

        // Validate image header
        if (! $this->hasValidImageHeader($fileContents)) {
            $errors[] = "Image header validation failed. File may be corrupted or malicious.";
        }

        // Check image dimensions (if possible)
        $dimensions = $this->getImageDimensions($fileContents);
        if ($dimensions) {
            ['width' => $width, 'height' => $height] = $dimensions;

            if ($width < self::MIN_DIMENSION || $height < self::MIN_DIMENSION) {
                $warnings[] = "Image is very small ({$width}x{$height}). OCR quality may be poor.";
            }

            if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
                $warnings[] = "Image is very large ({$width}x{$height}). May slow down processing.";
            }
        }

        // Basic malware signature check
        if ($this->hasEvilPatterns($fileContents)) {
            $errors[] = "File contains suspicious patterns. Possible malware.";
        }

        Log::info('Image validation', [
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'dimensions' => $dimensions,
            'valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
        ]);

        return [
            'valid' => count($errors) === 0,
            'error' => count($errors) > 0 ? implode('; ', $errors) : null,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate an uploaded PDF document.
     *
     * @param UploadedFile|string $file File object or path to file contents
     *
     * @return array{valid: bool, error: string|null, warnings: array}
     */
    public function validatePdf($file): array
    {
        $errors = [];
        $warnings = [];

        $fileContents = $this->getFileContents($file);
        $mimeType = $this->getMimeType($file, $fileContents);
        $fileSize = strlen($fileContents);

        // Check MIME type
        if ($mimeType !== 'application/pdf') {
            $errors[] = "Not a PDF file. MIME type: $mimeType";
        }

        // Check file size
        if ($fileSize < self::MIN_FILE_SIZE_BYTES) {
            $errors[] = "File is too small (possibly corrupted)";
        }

        if ($fileSize > self::MAX_PDF_SIZE_BYTES) {
            $errors[] = "PDF exceeds maximum size of 50 MB. Size: " . $this->formatBytes($fileSize);
        }

        // Check PDF header
        if (! str_starts_with($fileContents, '%PDF')) {
            $errors[] = "Invalid PDF header. File may be corrupted.";
        }

        // Basic malware check
        if ($this->hasEvilPatterns($fileContents)) {
            $errors[] = "File contains suspicious patterns.";
        }

        Log::info('PDF validation', [
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'valid' => count($errors) === 0,
            'errors' => $errors,
        ]);

        return [
            'valid' => count($errors) === 0,
            'error' => count($errors) > 0 ? implode('; ', $errors) : null,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get file contents from either UploadedFile or raw data.
     */
    private function getFileContents($file): string
    {
        if ($file instanceof UploadedFile) {
            return file_get_contents($file->getRealPath());
        }

        return (string) $file;
    }

    /**
     * Determine MIME type from file.
     */
    private function getMimeType($file, string $contents): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getMimeType() ?? 'application/octet-stream';
        }

        // Try to detect from content
        if (str_starts_with($contents, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($contents, "\x89\x50\x4E\x47")) {
            return 'image/png';
        }

        if (str_contains(substr($contents, 0, 20), 'WEBP')) {
            return 'image/webp';
        }

        if (str_starts_with($contents, '%PDF')) {
            return 'application/pdf';
        }

        return 'application/octet-stream';
    }

    /**
     * Validate image header.
     */
    private function hasValidImageHeader(string $content): bool
    {
        // JPEG header
        if (str_starts_with($content, "\xFF\xD8\xFF")) {
            return str_ends_with($content, "\xFF\xD9"); // JPEG end marker
        }

        // PNG header
        if (str_starts_with($content, "\x89\x50\x4E\x47")) {
            return true;
        }

        // WEBP header
        if (str_contains(substr($content, 0, 20), 'WEBP')) {
            return true;
        }

        return false;
    }

    /**
     * Get image dimensions from binary data.
     *
     * @return array{width: int, height: int}|null
     */
    private function getImageDimensions(string $content): ?array
    {
        $imageHandle = @imagecreatefromstring($content);

        if (! $imageHandle) {
            return null;
        }

        $dimensions = [
            'width' => imagesx($imageHandle),
            'height' => imagesy($imageHandle),
        ];

        imagedestroy($imageHandle);

        return $dimensions;
    }

    /**
     * Check for evil patterns/signatures that suggest malware.
     */
    private function hasEvilPatterns(string $content): bool
    {
        $evilPatterns = [
            '/eval\s*\(/i',
            '/system\s*\(/i',
            '/exec\s*\(/i',
            '/passthru\s*\(/i',
            '/shell_exec\s*\(/i',
            '/<script/i',
            '/javascript:/i',
            '/onclick=/i',
            '/onerror=/i',
        ];

        foreach ($evilPatterns as $pattern) {
            if (@preg_match($pattern, $content)) {
                Log::warning('Suspicious pattern detected in uploaded file', [
                    'pattern' => $pattern,
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Format bytes to human-readable size.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
