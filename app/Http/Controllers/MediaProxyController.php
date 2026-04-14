<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaProxyController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        // Normalize path - remove leading slashes and prevent directory traversal
        $path = trim($path, '/');
        if (str_contains($path, '..')) {
            abort(404);
        }

        $fullPath = storage_path('app/public/'.$path);

        if (! file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }
}
