<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SpmbSetting;
use App\Services\ImageService;
use Inertia\Inertia;

/**
 * Controller for public SPMB (PPDB) information page
 * Refactored from routes/web.php closure
 */
class SpmbController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display the SPMB information page
     */
    public function index()
    {
        $settings = SpmbSetting::with('media')->get()->keyBy('section_key');
        
        $spmbData = [];
        foreach (array_keys(SpmbSetting::getSectionFields()) as $key) {
            $dbRow = $settings->get($key);
            $dbContent = ($dbRow && isset($dbRow['content'])) ? $dbRow['content'] : null;
            $content = SpmbSetting::getContent($key, $dbContent);
            
            // Inject Media
            if ($dbRow) {
                if ($key === 'pengaturan_umum') {
                    $media = $this->imageService->getFirstMediaData($dbRow, 'banner_image');
                    if ($media) {
                        $content['banner_image'] = $media;
                    }
                }
            }
            
            $spmbData[$key] = $content;
        }
        
        return Inertia::render('InformasiSpmbPage', [
            'spmbData' => $spmbData
        ]);
    }
}
