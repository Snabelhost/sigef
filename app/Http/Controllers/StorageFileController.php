<?php

namespace App\Http\Controllers;

use App\Support\PublicStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFileController extends Controller
{
    /**
     * Serve ficheiros de storage/app/public sem depender de symlinks.
     */
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        $path = PublicStorage::normalizePath($path);

        abort_if($path === null, 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        $fullPath = $disk->path($path);
        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
        $lastModified = filemtime($fullPath) ?: time();
        $etag = hash('sha256', $path.'|'.$lastModified.'|'.filesize($fullPath));

        $response = response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ])->setContentDisposition('inline', basename($path));

        $response->setEtag($etag);
        $response->setLastModified(new \DateTimeImmutable('@'.$lastModified));
        $response->isNotModified($request);

        return $response;
    }
}
