<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class StorageFileController extends Controller
{
    /**
     * Serve ficheiros de storage/app/public.
     * Usado quando o Apache não consegue seguir o symlink (cPanel).
     */
    public function __invoke(string $path)
    {
        // Prevenir directory traversal
        $path = str_replace(['..', "\0"], '', $path);

        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            abort(404);
        }

        $fullPath = $disk->path($path);
        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }
}
