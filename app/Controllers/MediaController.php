<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;

/**
 * Streams uploaded media from storage/uploads, which lives outside the web
 * root. Nothing in that directory is ever executed: files are read and echoed
 * with an explicit, whitelisted image content type.
 */
final class MediaController
{
    private const TYPES = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
    ];

    public function show(array $params): void
    {
        $path = (string) ($params['path'] ?? '');

        // Reject traversal, absolute paths and null bytes outright.
        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0") || str_starts_with($path, '/')) {
            Response::abort(404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$ext])) {
            Response::abort(404);
        }

        $base = realpath((string) config('app.uploads.dir'));
        $full = realpath($base . '/' . $path);

        if ($base === false || $full === false || !str_starts_with($full, $base) || !is_file($full)) {
            Response::abort(404);
        }

        $mtime = (int) filemtime($full);
        $etag  = '"' . md5($full . $mtime) . '"';

        header('Content-Type: ' . self::TYPES[$ext]);
        header('Content-Length: ' . filesize($full));
        header('Cache-Control: public, max-age=31536000, immutable');
        header('ETag: ' . $etag);
        header('X-Content-Type-Options: nosniff');

        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            exit;
        }

        readfile($full);
        exit;
    }
}
