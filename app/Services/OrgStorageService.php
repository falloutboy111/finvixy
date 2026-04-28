<?php

namespace App\Services;

use App\Models\Organisation;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OrgStorageService
{
    protected Filesystem $disk;

    protected string $orgPrefix;

    public function __construct(
        protected Organisation $organisation,
    ) {
        $this->disk = Storage::disk('org-storage');
        $this->orgPrefix = 'org-'.$organisation->id;
    }

    /**
     * Initialise the org's folder structure on S3.
     */
    public function initialise(): void
    {
        $this->disk->put($this->orgPrefix.'/.keep', '');
    }

    /**
     * Store a file in the org's private folder.
     *
     * @return string The relative path within the org folder
     */
    public function store(string $subdirectory, UploadedFile $file): string
    {
        $this->ensureWithinStorageLimit($file->getSize());

        $path = $this->disk->putFile(
            $this->orgPrefix.'/'.ltrim($subdirectory, '/'),
            $file,
        );

        $this->organisation->increment('storage_used_bytes', $file->getSize());

        return $path;
    }

    /**
     * Store raw content (e.g. downloaded file bytes) in the org's private folder.
     *
     * @return string The relative path within the disk root
     */
    public function storeRaw(string $subdirectory, string $content, string $filename): string
    {
        $size = strlen($content);
        $this->ensureWithinStorageLimit($size);

        $path = $this->orgPrefix.'/'.ltrim($subdirectory, '/').'/'.$filename;
        $this->disk->put($path, $content);

        $this->organisation->increment('storage_used_bytes', $size);

        return $path;
    }

    /**
     * Get the underlying filesystem disk.
     */
    public function disk(): Filesystem
    {
        return $this->disk;
    }

    /**
     * Get the org prefix (e.g. "org-12").
     */
    public function orgPrefix(): string
    {
        return $this->orgPrefix;
    }

    /**
     * Get a temporary signed URL for a file (on-demand access).
     */
    public function temporaryUrl(string $path, int $minutes = 15): string
    {
        return $this->disk->temporaryUrl($path, now()->addMinutes($minutes));
    }

    /**
     * Delete a file and reclaim storage.
     */
    public function delete(string $path): bool
    {
        $size = $this->disk->size($path);
        $deleted = $this->disk->delete($path);

        if ($deleted && $size > 0) {
            $this->organisation->decrement('storage_used_bytes', min($size, $this->organisation->storage_used_bytes));
        }

        return $deleted;
    }

    /**
     * List files in a subdirectory for the org.
     *
     * @return array<int, string>
     */
    public function files(string $subdirectory = ''): array
    {
        return $this->disk->files($this->orgPrefix.'/'.ltrim($subdirectory, '/'));
    }

    /**
     * Get remaining storage in bytes.
     */
    public function remainingBytes(): int
    {
        return max(0, $this->organisation->storage_limit_bytes - $this->organisation->storage_used_bytes);
    }

    /**
     * Get storage usage as a percentage.
     */
    public function usagePercent(): float
    {
        if ($this->organisation->storage_limit_bytes === 0) {
            return 100.0;
        }

        return round(($this->organisation->storage_used_bytes / $this->organisation->storage_limit_bytes) * 100, 1);
    }

    /**
     * Check if the org has exceeded their storage limit.
     */
    protected function ensureWithinStorageLimit(int $additionalBytes): void
    {
        if (($this->organisation->storage_used_bytes + $additionalBytes) > $this->organisation->storage_limit_bytes) {
            throw new \RuntimeException('Storage limit exceeded. Current usage: '.
                $this->formatBytes($this->organisation->storage_used_bytes).
                ' / '.$this->formatBytes($this->organisation->storage_limit_bytes));
        }
    }

    /**
     * Format bytes into human-readable string.
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 1).' KB';
    }
}
