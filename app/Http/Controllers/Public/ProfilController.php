<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SchoolProfileSetting;
use App\Models\LandingPageSetting;
use App\Services\ImageService;
use Inertia\Inertia;

/**
 * Controller for public school profile pages
 * Refactored from routes/web.php closure
 */
class ProfilController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display the school profile page (history & facilities)
     */
    public function profilSekolah()
    {
        $settings = SchoolProfileSetting::with('media')->get()->keyBy('section_key');
        $landingSettings = LandingPageSetting::with('media')->get()->keyBy('section_key');
        
        // Helper untuk mendapatkan konten dari settings
        $getSettingContent = function($key) use ($settings) {
            $content = $settings->get($key)?->content;
            if (is_string($content)) {
                $decoded = json_decode($content, true);
                return is_array($decoded) ? $decoded : null;
            }
            return $content;
        };

        $getLandingContent = function($key) use ($landingSettings) {
            $content = $landingSettings->get($key)?->content;
            if (is_string($content)) {
                $decoded = json_decode($content, true);
                return is_array($decoded) ? $decoded : null;
            }
            return $content;
        };
        
        $hero = SchoolProfileSetting::getContent('hero_history', $getSettingContent('hero_history'));
        // Inject Media
        $heroRow = $settings->get('hero_history');
        if ($heroRow) {
            $bgMedia = $this->imageService->getFirstMediaData($heroRow, 'hero_history_bg');
            if ($bgMedia) {
                $hero['backgroundImage'] = $bgMedia;
            }
        }

        $history = SchoolProfileSetting::getContent('history', $getSettingContent('history'));
        $facilities = SchoolProfileSetting::getContent('facilities', $getSettingContent('facilities'));
        
        // Inject Media for facilities items
        $facilitiesRow = $settings->get('facilities');
        if ($facilitiesRow && isset($facilities['items']) && is_array($facilities['items'])) {
            foreach ($facilities['items'] as $index => &$item) {
                $media = $this->imageService->getFirstMediaData($facilitiesRow, "facilities_item_{$index}");
                if ($media) {
                    $item['image'] = $media;
                    $item['image_url'] = $media['original_url'];
                }
            }
        }

        // Fetch Landing Page content for About and CTA
        $aboutContent = LandingPageSetting::getContent('about_lp', $getLandingContent('about_lp'));
        $programsContent = LandingPageSetting::getContent('programs_lp', $getLandingContent('programs_lp'));
        $ctaContent = LandingPageSetting::getContent('cta_lp', $getLandingContent('cta_lp'));
        
        // Inject media for About section
        $aboutRow = $landingSettings->get('about_lp');
        if ($aboutRow) {
            $aboutMedia = $this->imageService->getFirstMediaData($aboutRow, 'about_image');
            if ($aboutMedia) {
                $aboutContent['aboutImage'] = $aboutMedia;
            }
        }
        
        return Inertia::render('ProfilSekolahPage', [
            'hero' => $hero,
            'history' => $history,
            'facilities' => $facilities,
            'aboutContent' => $aboutContent,
            'programsContent' => $programsContent,
            'ctaContent' => $ctaContent,
        ]);
    }

    /**
     * Display the vision & mission page
     */
    public function visiMisi()
    {
        $settings = SchoolProfileSetting::with('media')->get()->keyBy('section_key');
        
        $getSettingContent = function($key) use ($settings) {
            $content = $settings->get($key)?->content;
            if (is_string($content)) {
                $decoded = json_decode($content, true);
                return is_array($decoded) ? $decoded : null;
            }
            return $content;
        };
        
        $hero = SchoolProfileSetting::getContent('hero_vision_mission', $getSettingContent('hero_vision_mission'));
        
        // Inject Media
        $heroRow = $settings->get('hero_vision_mission');
        if ($heroRow) {
            $bgMedia = $this->imageService->getFirstMediaData($heroRow, 'hero_vision_mission_bg');
            if ($bgMedia) {
                $hero['backgroundImage'] = $bgMedia;
            }
        }

        $visionMission = SchoolProfileSetting::getContent('vision_mission', $getSettingContent('vision_mission'));
        
        return Inertia::render('VisiMisiPage', [
            'hero' => $hero,
            'visionMission' => $visionMission
        ]);
    }

    /**
     * Display the organization structure page
     */
    public function strukturOrganisasi()
    {
        $settings = SchoolProfileSetting::with('media')->get()->keyBy('section_key');
        
        $getSettingContent = function($key) use ($settings) {
            $content = $settings->get($key)?->content;
            if (is_string($content)) {
                $decoded = json_decode($content, true);
                return is_array($decoded) ? $decoded : null;
            }
            return $content;
        };
        
        $hero = SchoolProfileSetting::getContent('hero_organization', $getSettingContent('hero_organization'));
        // Inject Media Hero
        $heroRow = $settings->get('hero_organization');
        if ($heroRow) {
            $bgMedia = $this->imageService->getFirstMediaData($heroRow, 'hero_organization_bg');
            if ($bgMedia) {
                $hero['backgroundImage'] = $bgMedia;
            }
        }

        $organization = SchoolProfileSetting::getContent('organization', $getSettingContent('organization'));
        // Inject Media Chart
        $orgRow = $settings->get('organization');
        if ($orgRow) {
            $chartMedia = $this->imageService->getFirstMediaData($orgRow, 'organization');
            if ($chartMedia) {
                $organization['chartImage'] = $chartMedia;
            }
        }
        
        return Inertia::render('StrukturOrganisasiPage', [
            'hero' => $hero,
            'organization' => $organization
        ]);
    }
}
