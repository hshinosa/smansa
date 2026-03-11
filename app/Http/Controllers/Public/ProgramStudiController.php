<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ProgramStudiSetting;
use App\Services\ImageService;
use Inertia\Inertia;

/**
 * Controller for public program studi pages (MIPA, IPS, Bahasa)
 * Refactored from routes/web.php closure
 */
class ProgramStudiController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Get program studi data by program name
     */
    protected function getProgramData(string $programName): array
    {
        $settings = ProgramStudiSetting::where('program_name', $programName)
            ->with('media')
            ->get()
            ->keyBy('section_key');
        
        $pageData = [];
        foreach (array_keys(ProgramStudiSetting::getSectionFields()) as $key) {
            $dbRow = $settings->get($key);
            $dbContent = ($dbRow && isset($dbRow['content'])) ? $dbRow['content'] : null;
            $content = ProgramStudiSetting::getContent($key, $dbContent);
            
            // Inject Media
            if ($dbRow) {
                if ($key === 'hero') {
                    $media = $this->imageService->getFirstMediaData($dbRow, 'hero_bg');
                    if ($media) {
                        $content['background_image'] = $media;
                    }
                }
                if ($key === 'facilities') {
                    $media = $this->imageService->getFirstMediaData($dbRow, 'facilities_main_image');
                    if ($media) {
                        $content['main_image'] = $media;
                    }

                    // Inject images for facility items list
                    if (isset($content['items']) && is_array($content['items'])) {
                        foreach ($content['items'] as $index => &$item) {
                            $itemMedia = $this->imageService->getFirstMediaData($dbRow, "facilities_item_{$index}_image");
                            if ($itemMedia) {
                                $item['image'] = $itemMedia['original_url'];
                            }
                        }
                    }
                }
            }
            
            $pageData[$key] = $content;
        }
        
        return $pageData;
    }

    /**
     * Display the MIPA program page
     */
    public function mipa()
    {
        return Inertia::render('ProgramMipaPage', [
            'content' => $this->getProgramData('mipa')
        ]);
    }

    /**
     * Display the IPS program page
     */
    public function ips()
    {
        return Inertia::render('ProgramIpsPage', [
            'content' => $this->getProgramData('ips')
        ]);
    }

    /**
     * Display the Bahasa program page
     */
    public function bahasa()
    {
        return Inertia::render('ProgramBahasaPage', [
            'content' => $this->getProgramData('bahasa')
        ]);
    }
}
