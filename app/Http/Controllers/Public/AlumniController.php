<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Services\ImageService;
use Inertia\Inertia;

/**
 * Controller for public alumni page
 * Refactored from routes/web.php closure
 */
class AlumniController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display the alumni testimonials page
     */
    public function index()
    {
        $alumnis = Alumni::where('is_published', true)
            ->orderBy('sort_order')
            ->latest()
            ->with('media')
            ->get()
            ->map(function ($alumni) {
                $data = $alumni->toArray();
                
                $testimonialImages = $this->imageService->getOrderedMediaData($alumni, 'testimonial_images');
                if (!empty($testimonialImages)) {
                    $data['testimonialImages'] = $testimonialImages;
                    $data['image_url'] = $testimonialImages[0]['original_url'] ?? $data['image_url'];
                }
                 
                // Get video media for uploaded videos
                if ($alumni->content_type === 'video' && $alumni->video_source === 'upload') {
                    $videoMedia = $alumni->getFirstMedia('videos');
                    if ($videoMedia) {
                        $videoUrl = $this->imageService->getMediaUrl($videoMedia);
                        $data['video_url'] = $videoUrl;
                        $data['videoMedia'] = [
                            'id' => $videoMedia->id,
                            'original_url' => $videoUrl,
                            'mime_type' => $videoMedia->mime_type,
                            'file_name' => $videoMedia->file_name,
                            'size' => $videoMedia->size,
                        ];
                    }
                }
                
                // Get video thumbnail
                $thumbnailMedia = $this->imageService->getFirstMediaData($alumni, 'video_thumbnails');
                if ($thumbnailMedia) {
                    $data['video_thumbnail_url'] = $thumbnailMedia['original_url'];
                    $data['videoThumbnailImage'] = $thumbnailMedia;
                }
                
                return $data;
            });
        
        return Inertia::render('AlumniPage', [
            'alumnis' => $alumnis
        ]);
    }
}
