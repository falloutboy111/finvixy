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
        $this->folderName = $this->sanitizeFolderName($organisationName).'-finvixy';
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
    public function uploadFile(string $filename, string $content, string $mimeType = 'application/pdf', ?string $subfolder = null): array
    {
        $folderId = $subfolder ? $this->getOrCreateSubfolder($subfolder) : $this->getOrCreateFolder();

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
     * Get or create the main organisation folder.
     */
    public function getOrCreateFolder(): string
    {
        if ($this->folderId) {
            return $this->folderId;
        }

        $response = $this->service->files->listFiles([
            'q' => "mimeType='application/vnd.google-apps.folder' and name='{$this->folderName}' and trashed=false",
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
        ]);

        if (count($response->files) > 0) {
            $this->folderId = $response->files[0]->id;

            return $this->folderId;
        }

        $fileMetadata = new DriveFile([
            'name' => $this->folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);

        $folder = $this->service->files->create($fileMetadata, ['fields' => 'id']);
        $this->folderId = $folder->id;

        Log::info('Created Google Drive folder', [
            'folder_name' => $this->folderName,
            'folder_id' => $this->folderId,
        ]);

        return $this->folderId;
    }

    /**
     * Get or create a subfolder within the main folder.
     */
    public function getOrCreateSubfolder(string $subfolderName): string
    {
        $parentFolderId = $this->getOrCreateFolder();

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

    protected function sanitizeFolderName(string $name): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $name);
        $sanitized = preg_replace('/\s+/', ' ', trim($sanitized));

        return str_replace(' ', '-', $sanitized) ?: 'Organisation';
    }
}
