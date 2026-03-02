<?php

namespace App\Services;

use Aws\Textract\TextractClient;
use Illuminate\Support\Facades\Log;

class TextractService
{
    protected TextractClient $client;

    public function __construct()
    {
        $this->client = new TextractClient([
            'region' => config('services.textract.region'),
            'version' => config('services.textract.version', 'latest'),
            'credentials' => [
                'key' => config('services.textract.access_key'),
                'secret' => config('services.textract.secret_key'),
            ],
        ]);
    }

    /**
     * Run synchronous OCR on an image (JPEG/PNG/WEBP).
     * Returns the extracted text as a single string.
     */
    public function detectText(string $fileContents): string
    {
        try {
            $result = $this->client->detectDocumentText([
                'Document' => [
                    'Bytes' => $fileContents,
                ],
            ]);

            return $this->extractTextFromBlocks($result->get('Blocks') ?? []);
        } catch (\Throwable $e) {
            Log::error('Textract sync detection failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Start an async Textract job for PDF documents stored on S3.
     * Returns the Textract JobId.
     */
    public function startAsyncDetection(string $s3Bucket, string $s3Key): string
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

            return $result->get('JobId');
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
     * Get the result of an async Textract job.
     *
     * @return array{status: string, text: string|null}
     */
    public function getAsyncResult(string $jobId): array
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
                while ($nextToken) {
                    $nextResult = $this->client->getDocumentTextDetection([
                        'JobId' => $jobId,
                        'NextToken' => $nextToken,
                    ]);
                    $blocks = array_merge($blocks, $nextResult->get('Blocks') ?? []);
                    $nextToken = $nextResult->get('NextToken');
                }

                return [
                    'status' => 'SUCCEEDED',
                    'text' => $this->extractTextFromBlocks($blocks),
                ];
            }

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
