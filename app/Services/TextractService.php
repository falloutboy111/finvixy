<?php

namespace App\Services;

use Aws\Textract\TextractClient;
use Illuminate\Support\Facades\Log;

class TextractService
{
    use RetryableLaravelService;

    protected TextractClient $client;

    // Constants for limits
    private const MAX_IMAGE_SIZE_BYTES = 10485760; // 10 MB
    private const MIN_CONFIDENCE_THRESHOLD = 0.50;
    private const TIMEOUT_SECONDS = 30;

    public function __construct()
    {
        $this->client = new TextractClient([
            'region' => config('services.textract.region'),
            'version' => config('services.textract.version', 'latest'),
            'credentials' => [
                'key' => config('services.textract.access_key'),
                'secret' => config('services.textract.secret_key'),
            ],
            'http' => [
                'timeout' => self::TIMEOUT_SECONDS,
                'connect_timeout' => self::TIMEOUT_SECONDS,
            ],
        ]);
    }

    /**
     * Run synchronous OCR on an image (JPEG/PNG/WEBP) with validation and retry logic.
     * Returns the extracted text as a single string.
     */
    public function detectText(string $fileContents): string
    {
        // Input validation
        $this->validateImageInput($fileContents);

        return $this->executeWithRetry(
            fn() => $this->performDetection($fileContents),
            'Textract.detectText'
        );
    }

    /**
     * Perform the actual Textract detection (called by retry wrapper).
     */
    protected function performDetection(string $fileContents): string
    {
        try {
            $result = $this->client->detectDocumentText([
                'Document' => [
                    'Bytes' => $fileContents,
                ],
            ]);

            $text = $this->extractTextFromBlocks($result->get('Blocks') ?? []);

            Log::info('Textract detection successful', [
                'text_length' => strlen($text),
                'blocks_count' => count($result->get('Blocks') ?? []),
            ]);

            return $text;
        } catch (\Throwable $e) {
            Log::error('Textract detection failed', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate image input before sending to AWS.
     *
     * @throws \InvalidArgumentException
     */
    protected function validateImageInput(string $fileContents): void
    {
        // Check size
        if (strlen($fileContents) > self::MAX_IMAGE_SIZE_BYTES) {
            throw new \InvalidArgumentException(
                "Image exceeds maximum size of 10 MB. Size: " . (strlen($fileContents) / 1024 / 1024) . " MB"
            );
        }

        if (strlen($fileContents) < 100) {
            throw new \InvalidArgumentException('Image data is too small (possibly corrupted)');
        }

        // Check for valid image headers (JPEG, PNG, WEBP)
        $isValidImage = $this->hasValidImageHeader($fileContents);

        if (! $isValidImage) {
            throw new \InvalidArgumentException('Invalid image format. Supported: JPEG, PNG, WEBP');
        }
    }

    /**
     * Check if file content has a valid image header.
     */
    protected function hasValidImageHeader(string $content): bool
    {
        // PNG header
        if (str_starts_with($content, "\x89\x50\x4E\x47")) {
            return true;
        }

        // JPEG header
        if (str_starts_with($content, "\xFF\xD8\xFF")) {
            return true;
        }

        // WEBP header
        if (str_contains(substr($content, 0, 20), 'WEBP')) {
            return true;
        }

        return false;
    }

    /**
     * Start an async Textract job for PDF documents stored on S3 (with retry logic).
     * Returns the Textract JobId.
     */
    public function startAsyncDetection(string $s3Bucket, string $s3Key): string
    {
        return $this->executeWithRetry(
            fn() => $this->performAsyncStart($s3Bucket, $s3Key),
            'Textract.startAsyncDetection',
            2  // Max 2 retries for async start
        );
    }

    /**
     * Perform the actual async detection start (called by retry wrapper).
     */
    protected function performAsyncStart(string $s3Bucket, string $s3Key): string
    {
        try {
            $result = $this->client->startDocumentTextDetection([
                'DocumentLocation' => [
                    'S3Object' => [
                        'Bucket' => $s3Bucket,
                        'Name' => $s3Key,
                    ],
                ],
            ]);

            $jobId = $result->get('JobId');

            Log::info('Textract async job started', [
                'job_id' => $jobId,
                'bucket' => $s3Bucket,
                'key' => $s3Key,
            ]);

            return $jobId;
        } catch (\Throwable $e) {
            Log::error('Textract async start failed', [
                'bucket' => $s3Bucket,
                'key' => $s3Key,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the result of an async Textract job (with retry logic).
     *
     * @return array{status: string, text: string|null}
     */
    public function getAsyncResult(string $jobId): array
    {
        return $this->executeWithRetry(
            fn() => $this->performAsyncGet($jobId),
            'Textract.getAsyncResult',
            2
        );
    }

    /**
     * Perform the actual async result retrieval (called by retry wrapper).
     */
    protected function performAsyncGet(string $jobId): array
    {
        try {
            $result = $this->client->getDocumentTextDetection([
                'JobId' => $jobId,
            ]);

            $status = $result->get('JobStatus');

            if ($status === 'SUCCEEDED') {
                $blocks = $result->get('Blocks') ?? [];

                // Handle pagination — Textract may split results
                $nextToken = $result->get('NextToken');
                $pageCount = 1;
                while ($nextToken) {
                    $nextResult = $this->client->getDocumentTextDetection([
                        'JobId' => $jobId,
                        'NextToken' => $nextToken,
                    ]);
                    $blocks = array_merge($blocks, $nextResult->get('Blocks') ?? []);
                    $nextToken = $nextResult->get('NextToken');
                    $pageCount++;
                }

                $text = $this->extractTextFromBlocks($blocks);

                Log::info('Textract async job succeeded', [
                    'job_id' => $jobId,
                    'pages' => $pageCount,
                    'text_length' => strlen($text),
                ]);

                return [
                    'status' => 'SUCCEEDED',
                    'text' => $text,
                ];
            }

            Log::info('Textract async job status', [
                'job_id' => $jobId,
                'status' => $status,
            ]);

            return [
                'status' => $status,
                'text' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('Textract async result failed', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract LINE-type text from Textract blocks.
     */
    protected function extractTextFromBlocks(array $blocks): string
    {
        return collect($blocks)
            ->where('BlockType', 'LINE')
            ->pluck('Text')
            ->implode("\n");
    }
}
