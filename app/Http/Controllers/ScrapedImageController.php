<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScrapedImageController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        $fullPath = base_path('instagram-scraper/downloads/' . $path);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            abort(403);
        }

        return response()->file($fullPath);
    }
}
