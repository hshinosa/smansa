<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Services\ImageService;
use Inertia\Inertia;

/**
 * Controller for public gallery page
 * Refactored from routes/web.php closure
 */
class GaleriController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display the gallery page
     */
    public function index()
    {
        $galleries = Gallery::with('media')
            ->latest()
            ->get()
            ->map(function ($gallery) {
                $data = $gallery->toArray();
                $media = $this->imageService->getFirstMediaData($gallery, 'images');
                if ($media) {
                    $data['image'] = $media;
                }
                return $data;
            });
        
        return Inertia::render('GaleriPage', [
            'galleries' => $galleries
        ]);
    }
}
