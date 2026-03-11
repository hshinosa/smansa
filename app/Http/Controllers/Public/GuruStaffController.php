<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\ImageService;
use Inertia\Inertia;

/**
 * Controller for public teachers & staff page
 * Refactored from routes/web.php closure
 */
class GuruStaffController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display the teachers and staff page
     */
    public function index()
    {
        $teachers = Teacher::where('is_active', true)
            ->orderBy('sort_order')
            ->with('media')
            ->get()
            ->map(function ($teacher) {
                $data = $teacher->toArray();
                
                // Get photo from media library
                $photoMedia = $this->imageService->getFirstMediaData($teacher, 'photos');
                if ($photoMedia) {
                    $data['photosImage'] = $photoMedia;
                    $data['image_url'] = $photoMedia['original_url'];
                }
                
                return $data;
            });
        
        return Inertia::render('GuruStaffPage', [
            'teachers' => $teachers
        ]);
    }
}
