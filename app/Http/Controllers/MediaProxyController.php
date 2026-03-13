<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaProxyController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        $fullPath = storage_path('app/public/' . $path);
        $realBase = realpath(storage_path('app/public'));
        $realPath = realpath($fullPath);

        if (!$realPath || !str_starts_with($realPath, $realBase)) {
            abort(404);
        }

        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($realPath);
    }
}
