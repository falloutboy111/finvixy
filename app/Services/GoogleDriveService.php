<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Oauth2;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected GoogleClient $client;

    protected Drive $service;

    protected string $folderName;

    protected ?string $folderId = null;

    public function __construct(
        protected ConnectedAccount $account,
        string $organisationName,
    ) {
        $this->client = new GoogleClient;
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessToken($account->credentials['access_token']);

        // Refresh token if expired
        if ($this->client->isAccessTokenExpired() && isset($account->credentials['refresh_token'])) {
            $this->client->fetchAccessTokenWithRefreshToken($account->credentials['refresh_token']);

            $newToken = $this->client->getAccessToken();
            $account->update([
                'credentials' => array_merge($account->credentials, $newToken),
                'expires_at' => isset($newToken['expires_in']) ? now()->addSeconds($newToken['expires_in']) : null,
            ]);
        }

        $this->service = new Drive($this->client);

        // Prefer a user-typed root folder name over the auto-generated one.
        $rootName = $account->settings['drive_root_name'] ?? null;
        $this->folderName = $rootName
            ? $this->sanitizeFolderName($rootName)
            : $this->sanitizeFolderName($organisationName).'-finvixy';
    }

    /**
     * Build a Google Client configured for OAuth.
     */
    public static function makeOAuthClient(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));
        $client->addScope('https://www.googleapis.com/auth/drive.file');
        $client->addScope('https://www.googleapis.com/auth/userinfo.email');
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    /**
     * Generate the OAuth authorization URL.
     *
     * @param  array<string, mixed>  $stateData
     */
    public static function getAuthUrl(array $stateData = []): string
    {
        $client = static::makeOAuthClient();
        $client->setState(base64_encode(json_encode($stateData)));

        return $client->createAuthUrl();
    }

    /**
     * Exchange an authorization code for tokens and return user info.
     *
     * @return array{token: array<string, mixed>, email: string}
     */
    public static function exchangeCode(string $code): array
    {
        $client = static::makeOAuthClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \RuntimeException($token['error_description'] ?? $token['error']);
        }

        $client->setAccessToken($token);
        $oauth2 = new Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        return [
            'token' => $token,
            'email' => $userInfo->email,
        ];
    }

    /**
     * Upload a file to Google Drive.
     *
     * @return array{id: string, webViewLink: string}
     */
    public function uploadFile(string $filename, string $content, string $mimeType = 'application/pdf', ?string $subfolder = null, ?string $customFolderId = null): array
    {
        $folderId = $subfolder
            ? $this->getOrCreateSubfolder($subfolder, $customFolderId)
            : $this->getOrCreateFolder($customFolderId);

        $fileMetadata = new DriveFile([
            'name' => $filename,
            'parents' => [$folderId],
        ]);

        $file = $this->service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id, name, webViewLink',
        ]);

        Log::info('Uploaded file to Google Drive', [
            'filename' => $filename,
            'file_id' => $file->id,
            'web_link' => $file->webViewLink,
        ]);

        return [
            'id' => $file->id,
            'webViewLink' => $file->webViewLink,
        ];
    }

    /**
     * Get or create the main folder, respecting a user-configured folder ID if set.
     */
    public function getOrCreateFolder(?string $customFolderId = null): string
    {
        if ($this->folderId) {
            return $this->folderId;
        }

        // Use the user-configured folder if provided and it still exists
        if ($customFolderId) {
            try {
                $folder = $this->service->files->get($customFolderId, ['fields' => 'id, trashed']);
                if (! $folder->trashed) {
                    $this->folderId = $customFolderId;
                    return $this->folderId;
                }
            } catch (\Throwable) {
                // Folder was deleted or permission revoked — fall through to auto-create
                Log::warning('GoogleDriveService: custom folder not accessible, falling back to default', [
                    'folder_id' => $customFolderId,
                ]);
            }
        }

        $response = $this->service->files->listFiles([
            'q'       => "mimeType='application/vnd.google-apps.folder' and name='{$this->folderName}' and trashed=false",
            'spaces'  => 'drive',
            'fields'  => 'files(id, name)',
            'orderBy' => 'createdTime',  // oldest first — picks the original if duplicates exist
        ]);

        if (count($response->files) > 0) {
            $this->folderId = $response->files[0]->id;
            $this->pinFolderIdToAccount($this->folderId);

            return $this->folderId;
        }

        $fileMetadata = new DriveFile([
            'name' => $this->folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);

        $folder = $this->service->files->create($fileMetadata, ['fields' => 'id']);
        $this->folderId = $folder->id;
        $this->pinFolderIdToAccount($this->folderId);

        Log::info('Created Google Drive folder', [
            'folder_name' => $this->folderName,
            'folder_id' => $this->folderId,
        ]);

        return $this->folderId;
    }

    /**
     * List all non-trashed folders the user can access, ordered by name.
     * Returns an array of ['id' => ..., 'name' => ..., 'path' => ...].
     *
     * @return array<int, array{id: string, name: string, path: string}>
     */
    public function listFolders(string $search = ''): array
    {
        $nameFilter = $search
            ? " and name contains '".addslashes($search)."'"
            : '';

        $response = $this->service->files->listFiles([
            'q' => "mimeType='application/vnd.google-apps.folder' and trashed=false{$nameFilter}",
            'spaces' => 'drive',
            'fields' => 'files(id, name, parents)',
            'orderBy' => 'name',
            'pageSize' => 100,
        ]);

        return collect($response->files)
            ->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'path' => $f->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Get or create a subfolder within the main (or custom) folder.
     */
    public function getOrCreateSubfolder(string $subfolderName, ?string $customFolderId = null): string
    {
        $parentFolderId = $this->getOrCreateFolder($customFolderId);

        $response = $this->service->files->listFiles([
            'q' => "mimeType='application/vnd.google-apps.folder' and name='{$subfolderName}' and '{$parentFolderId}' in parents and trashed=false",
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
        ]);

        if (count($response->files) > 0) {
            return $response->files[0]->id;
        }

        $fileMetadata = new DriveFile([
            'name' => $subfolderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentFolderId],
        ]);

        $folder = $this->service->files->create($fileMetadata, ['fields' => 'id']);

        Log::info('Created Google Drive subfolder', [
            'subfolder_name' => $subfolderName,
            'subfolder_id' => $folder->id,
            'parent_folder_id' => $parentFolderId,
        ]);

        return $folder->id;
    }

    /**
     * Walk a slash-delimited path (e.g. "v1/receipts") inside $parentId,
     * creating any missing folder segments along the way.
     * Returns the ID of the deepest folder.
     */
    public function navigatePath(string $parentId, string $path): string
    {
        $segments = array_filter(explode('/', $path), fn ($s) => $s !== '');

        foreach ($segments as $segment) {
            $parentId = $this->getOrCreateNamedFolder($this->sanitizeFolderName($segment), $parentId);
        }

        return $parentId;
    }

    /**
     * Upload a file directly into a pre-resolved folder ID.
     * Use this after navigatePath() resolves the target.
     *
     * @return array{id: string, webViewLink: string}
     */
    public function uploadFileToFolder(string $filename, string $content, string $mimeType, string $folderId): array
    {
        $fileMetadata = new DriveFile([
            'name'    => $filename,
            'parents' => [$folderId],
        ]);

        $file = $this->service->files->create($fileMetadata, [
            'data'       => $content,
            'mimeType'   => $mimeType,
            'uploadType' => 'multipart',
            'fields'     => 'id, name, webViewLink',
        ]);

        Log::info('Uploaded file to Google Drive', [
            'filename'  => $filename,
            'file_id'   => $file->id,
            'web_link'  => $file->webViewLink,
            'folder_id' => $folderId,
        ]);

        return [
            'id'          => $file->id,
            'webViewLink' => $file->webViewLink,
        ];
    }

    /**
     * Get or create a named folder directly inside a given parent.
     */
    private function getOrCreateNamedFolder(string $name, string $parentId): string
    {
        $escapedName = addslashes($name);

        $response = $this->service->files->listFiles([
            'q'      => "mimeType='application/vnd.google-apps.folder' and name='{$escapedName}' and '{$parentId}' in parents and trashed=false",
            'spaces' => 'drive',
            'fields' => 'files(id)',
        ]);

        if (count($response->files) > 0) {
            return $response->files[0]->id;
        }

        $folder = $this->service->files->create(new DriveFile([
            'name'     => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents'  => [$parentId],
        ]), ['fields' => 'id']);

        Log::info('Created Google Drive folder', [
            'name'      => $name,
            'id'        => $folder->id,
            'parent_id' => $parentId,
        ]);

        return $folder->id;
    }

    /**
     * Persist a discovered/created root folder ID to account settings so future
     * lookups use the ID directly instead of a name search (prevents duplicate folders).
     */
    private function pinFolderIdToAccount(string $folderId): void
    {
        $settings = $this->account->settings ?? [];
        if (($settings['drive_folder_id'] ?? null) === $folderId) {
            return;
        }
        $settings['drive_folder_id'] = $folderId;
        $this->account->update(['settings' => $settings]);
    }

    protected function sanitizeFolderName(string $name): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $name);
        $sanitized = preg_replace('/\s+/', ' ', trim($sanitized));

        return str_replace(' ', '-', $sanitized) ?: 'Organisation';
    }
}
