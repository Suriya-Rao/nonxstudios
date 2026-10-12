<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Secure image upload handler.
 *
 * - Detects the real MIME type with finfo (never trusts the client).
 * - Verifies the bytes actually decode as the claimed image format.
 * - Generates randomized filenames with a safe extension.
 * - Enforces a size ceiling.
 * - Writes into storage/uploads (outside the web root); files are streamed
 *   back through a controller, so nothing inside the upload directory is ever
 *   executed by PHP.
 */
final class Uploader
{
    private const EXT_MAP = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    /**
     * @param array $file A single $_FILES entry.
     * @return array{ok:bool,path?:string,error?:string}
     */
    public static function store(array $file, string $subdir = ''): array
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'No file was uploaded, or the upload failed.'];
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => self::uploadErrorMessage((int) $file['error'])];
        }

        $maxBytes = (int) config('app.uploads.max_bytes');
        if (($file['size'] ?? 0) > $maxBytes) {
            return ['ok' => false, 'error' => 'That file is larger than the ' . self::humanBytes($maxBytes) . ' limit.'];
        }

        // Real MIME detection from file contents.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']) ?: '';
        $allowed = (array) config('app.uploads.allowed');

        if (!in_array($mime, $allowed, true) || !isset(self::EXT_MAP[$mime])) {
            return ['ok' => false, 'error' => 'Unsupported file type. Allowed formats: JPEG, PNG, WebP, AVIF.'];
        }

        // Confirm the payload is a genuine image of that type.
        if (function_exists('getimagesize') && @getimagesize($file['tmp_name']) === false) {
            return ['ok' => false, 'error' => 'That file is not a readable image.'];
        }

        $ext    = self::EXT_MAP[$mime];
        $dir    = rtrim((string) config('app.uploads.dir'), '/');
        if ($subdir !== '') {
            $dir .= '/' . trim($subdir, '/');
        }

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            Logger::error('Unable to create upload directory', ['dir' => $dir]);
            return ['ok' => false, 'error' => 'The server could not store that file. Please try again.'];
        }

        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $name;

        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            Logger::error('move_uploaded_file failed', ['dest' => $dest]);
            return ['ok' => false, 'error' => 'The server could not store that file. Please try again.'];
        }

        @chmod($dest, 0644);

        $relative = ($subdir !== '' ? trim($subdir, '/') . '/' : '') . $name;
        return ['ok' => true, 'path' => $relative];
    }

    public static function delete(?string $relative): void
    {
        if ($relative === null || $relative === '') {
            return;
        }
        // Block traversal attempts.
        if (str_contains($relative, '..') || str_starts_with($relative, '/')) {
            return;
        }
        $full = rtrim((string) config('app.uploads.dir'), '/') . '/' . $relative;
        if (is_file($full)) {
            @unlink($full);
        }
    }

    public static function humanBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024) . ' KB';
        }
        return $bytes . ' B';
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
            UPLOAD_ERR_PARTIAL    => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE    => 'Please choose a file to upload.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server is missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'The server failed to write the file to disk.',
            default               => 'The upload could not be completed.',
        };
    }
}
