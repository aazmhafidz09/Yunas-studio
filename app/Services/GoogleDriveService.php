<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    /**
     * Cache duration in seconds (15 minutes).
     */
    protected const CACHE_TTL = 900;

    /**
     * Extract folder ID from various Google Drive URL formats.
     *
     * @param string|null $url
     * @return string|null
     */
    public function extractFolderId(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $url = trim($url);

        // Direct ID (typically 25-45 alphanumeric characters with dashes/underscores)
        if (preg_match('/^[a-zA-Z0-9_-]{25,}$/', $url)) {
            return $url;
        }

        // Standard folder URL: https://drive.google.com/drive/folders/{folderId}
        // or /drive/u/0/folders/{folderId}
        if (preg_match('/\/folders\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Open by ID: https://drive.google.com/open?id={folderId}
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Fetch photos from a Google Drive URL.
     *
     * @param string|null $url
     * @param bool $forceRefresh
     * @return array
     */
    public function getPhotosFromUrl(?string $url, bool $forceRefresh = false): array
    {
        $folderId = $this->extractFolderId($url);

        if (!$folderId) {
            return [
                'success' => false,
                'folder_id' => null,
                'original_url' => $url,
                'error' => 'INVALID_URL',
                'message' => 'Link Google Drive tidak valid atau tidak memuat ID folder yang benar.',
                'files' => [],
                'count' => 0,
            ];
        }

        return $this->getPhotosFromFolder($folderId, $url, $forceRefresh);
    }

    /**
     * Fetch photos inside a Google Drive folder using Google Drive API v3.
     *
     * @param string $folderId
     * @param string|null $originalUrl
     * @param bool $forceRefresh
     * @return array
     */
    public function getPhotosFromFolder(string $folderId, ?string $originalUrl = null, bool $forceRefresh = false): array
    {
        $apiKey = config('services.google_drive.api_key');

        if (empty($apiKey)) {
            return [
                'success' => false,
                'folder_id' => $folderId,
                'original_url' => $originalUrl,
                'error' => 'API_KEY_NOT_CONFIGURED',
                'message' => 'Google Drive API Key belum dikonfigurasi. Silakan tambahkan GOOGLE_DRIVE_API_KEY di file .env Anda.',
                'files' => [],
                'count' => 0,
            ];
        }

        $cacheKey = "gdrive_photos_{$folderId}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = (function () use ($folderId, $apiKey, $originalUrl) {
            try {
                $response = Http::timeout(15)->get('https://www.googleapis.com/drive/v3/files', [
                    'q' => "'{$folderId}' in parents and mimeType contains 'image/' and trashed = false",
                    'fields' => 'files(id, name, mimeType, webViewLink, webContentLink, thumbnailLink, size, createdTime)',
                    'orderBy' => 'name',
                    'pageSize' => 100,
                    'key' => $apiKey,
                ]);

                if ($response->successful()) {
                    $rawFiles = $response->json('files', []);
                    $formattedFiles = [];

                    foreach ($rawFiles as $file) {
                        $id = $file['id'] ?? '';
                        $size = (int) ($file['size'] ?? 0);
                        $rawThumbnail = $file['thumbnailLink'] ?? '';

                        // Use Google User Content CDN (lh3.googleusercontent.com) from thumbnailLink
                        if (!empty($rawThumbnail)) {
                            $thumbnailUrl = preg_replace('/=s\d+.*$/', '=s500', $rawThumbnail);
                            $previewUrl = preg_replace('/=s\d+.*$/', '=s1600', $rawThumbnail);
                        } else {
                            $thumbnailUrl = "https://lh3.googleusercontent.com/d/{$id}=s500";
                            $previewUrl = "https://lh3.googleusercontent.com/d/{$id}=s1600";
                        }

                        $formattedFiles[] = [
                            'id' => $id,
                            'name' => $file['name'] ?? 'Foto',
                            'mime_type' => $file['mimeType'] ?? 'image/jpeg',
                            'size_bytes' => $size,
                            'size_formatted' => $this->formatFileSize($size),
                            'created_at' => isset($file['createdTime']) ? date('d M Y H:i', strtotime($file['createdTime'])) : null,
                            'thumbnail_url' => $thumbnailUrl,
                            'preview_url' => $previewUrl,
                            'fallback_thumbnail' => "https://drive.google.com/thumbnail?id={$id}&sz=w500",
                            'download_url' => "https://drive.google.com/uc?export=download&id={$id}",
                            'web_view_url' => $file['webViewLink'] ?? "https://drive.google.com/file/d/{$id}/view",
                        ];
                    }

                    return [
                        'success' => true,
                        'folder_id' => $folderId,
                        'original_url' => $originalUrl,
                        'error' => null,
                        'message' => null,
                        'files' => $formattedFiles,
                        'count' => count($formattedFiles),
                    ];
                }

                $status = $response->status();
                $errorData = $response->json('error', []);
                $apiMessage = $errorData['message'] ?? 'Gagal menghubungi Google Drive API';

                Log::warning("Google Drive API error ({$status}): {$apiMessage}", [
                    'folderId' => $folderId,
                    'error' => $errorData,
                ]);

                $userMessage = match ($status) {
                    404 => 'Folder Google Drive tidak ditemukan. Mohon pastikan link folder sudah benar.',
                    403 => 'Akses ditolak oleh Google Drive. Pastikan folder telah diatur ke "Siapa saja yang memiliki link (Viewer)" dan Google Drive API telah diaktifkan di Google Cloud Console.',
                    400 => 'Permintaan ke Google Drive tidak valid. Periksa format ID folder.',
                    default => "Gagal memuat foto dari Google Drive: {$apiMessage}",
                };

                return [
                    'success' => false,
                    'folder_id' => $folderId,
                    'original_url' => $originalUrl,
                    'error' => "HTTP_{$status}",
                    'message' => $userMessage,
                    'files' => [],
                    'count' => 0,
                ];

            } catch (\Exception $e) {
                // Exception messages from cURL contain the full request URL (including the API key),
                // so redact the key before logging and never expose the raw message to users.
                Log::error('Google Drive API Exception: ' . str_replace($apiKey, '[REDACTED]', $e->getMessage()), [
                    'folderId' => $folderId,
                    'exception' => get_class($e),
                ]);

                return [
                    'success' => false,
                    'folder_id' => $folderId,
                    'original_url' => $originalUrl,
                    'error' => 'EXCEPTION',
                    'message' => 'Terjadi kesalahan saat menghubungi server Google Drive. Silakan coba lagi beberapa saat lagi atau buka folder langsung di Google Drive.',
                    'files' => [],
                    'count' => 0,
                ];
            }
        })();

        // Only cache successful responses so transient failures don't stick around for the whole TTL.
        if ($result['success']) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        }

        return $result;
    }

    /**
     * Format byte size to human readable format.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 1) . ' ' . ($units[$i] ?? 'B');
    }

    /**
     * Look up the file name of a single Drive file by its id, wherever it lives now
     * (e.g. a selected RAW photo that the editor has since moved to the result folder).
     * Successful lookups are cached for a day; returns null when the file cannot be read.
     */
    public function getFileName(string $fileId): ?string
    {
        $apiKey = config('services.google_drive.api_key');
        if (empty($apiKey) || !preg_match('/^[a-zA-Z0-9_-]+$/', $fileId)) {
            return null;
        }

        $cacheKey = "gdrive_file_name_{$fileId}";
        if (($cached = Cache::get($cacheKey)) !== null) {
            return $cached;
        }

        try {
            $response = Http::timeout(10)->get("https://www.googleapis.com/drive/v3/files/{$fileId}", [
                'fields' => 'name',
                'key' => $apiKey,
            ]);
            $name = $response->successful() ? $response->json('name') : null;
        } catch (\Exception $e) {
            Log::warning('Google Drive file lookup failed: ' . str_replace($apiKey, '[REDACTED]', $e->getMessage()), [
                'fileId' => $fileId,
            ]);
            $name = null;
        }

        if ($name) {
            Cache::put($cacheKey, $name, 86400);
        }

        return $name;
    }
}

