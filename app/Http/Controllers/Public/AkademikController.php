<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Models\CurriculumSetting;
use App\Models\AcademicCalendarContent;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Services\ImageService;
use Inertia\Inertia;

/**
 * Controller for public academic pages
 * Refactored from routes/web.php closure
 */
class AkademikController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Redirect old ekstrakurikuler URL to new organisasi-ekstrakurikuler URL
     */
    public function ekstrakurikuler()
    {
        return redirect()->route('akademik.organisasi_ekstrakurikuler');
    }

    /**
     * Display the extracurricular organization page (alias)
     */
    public function organisasiEkstrakurikuler()
    {
        $extracurriculars = Extracurricular::where('is_active', true)
            ->orderBy('sort_order')
            ->with('media')
            ->get()
            ->map(function ($ekskul) {
                // toArray() already includes accessors (image_url, bg_image_url, profile_image_url)
                return $ekskul->toArray();
            });

        return Inertia::render('EkstrakurikulerPage', [
            'extracurriculars' => $extracurriculars
        ]);
    }

    /**
     * Display the curriculum page
     */
    public function kurikulum()
    {
        $programStudiThumbnails = \App\Models\ProgramStudiSetting::with('media')
            ->where('section_key', 'hero')
            ->get()
            ->mapWithKeys(function ($setting) {
                $thumbnail = null;
                $thumbnailUrl = null;
                
                $thumbnailMedia = $this->imageService->getFirstMediaData($setting, 'thumbnail_card');
                if ($thumbnailMedia) {
                    $thumbnail = $thumbnailMedia;
                    $thumbnailUrl = $thumbnailMedia['original_url'] ?? null;
                } else {
                    $media = $setting->getFirstMedia('thumbnail_card');
                    if ($media) {
                        $thumbnailUrl = $this->imageService->getMediaUrl($media);
                    } elseif ($setting->thumbnail_card_url) {
                        $thumbnailUrl = asset($setting->thumbnail_card_url);
                    }
                }
                
                return [$setting->program_name => [
                    'media' => $thumbnail,
                    'url' => $thumbnailUrl
                ]];
            });
        
        $programs = Program::where('category', 'Program Studi')
            ->orderBy('sort_order')
            ->with('media')
            ->get()
            ->map(function ($program) use ($programStudiThumbnails) {
                $data = $program->toArray();
                $programTitle = strtolower($program->title ?? '');
                $programKey = null;
                
                if (str_contains($programTitle, 'mipa') || $programTitle === 'mipa') {
                    $programKey = 'mipa';
                } elseif (str_contains($programTitle, 'ips') || $programTitle === 'ips') {
                    $programKey = 'ips';
                } elseif (str_contains($programTitle, 'bahasa') || $programTitle === 'bahasa') {
                    $programKey = 'bahasa';
                } else {
                    $programSlug = strtolower($program->slug ?? '');
                    if (str_contains($programSlug, 'mipa') || str_contains($programSlug, 'ipa')) {
                        $programKey = 'mipa';
                    } elseif (str_contains($programSlug, 'ips')) {
                        $programKey = 'ips';
                    } elseif (str_contains($programSlug, 'bahasa')) {
                        $programKey = 'bahasa';
                    }
                }
                
                $data['image'] = null;
                $data['image_url'] = null;
                
                if ($programKey && isset($programStudiThumbnails[$programKey])) {
                    $thumbnailData = $programStudiThumbnails[$programKey];
                    if (!empty($thumbnailData['media'])) {
                        $data['image'] = $thumbnailData['media'];
                    }
                    if (!empty($thumbnailData['url'])) {
                        $data['image_url'] = $thumbnailData['url'];
                    }
                }
                
                if (empty($data['image']) && empty($data['image_url'])) {
                    $media = $this->imageService->getFirstMediaData($program, 'program_image');
                    if ($media) {
                        $data['image'] = $media;
                        $data['image_url'] = $media['original_url'] ?? null;
                    } else {
                        $data['image_url'] = $program->image_name ? "/images/{$program->image_name}" : "/images/anak-sma-programstudi.png";
                    }
                }
                
                return $data;
            });

        $settings = CurriculumSetting::with('media')->get()->keyBy('section_key');
        $mediaCollections = CurriculumSetting::getMediaCollections();
        
        $curriculumData = [];
        foreach (array_keys(CurriculumSetting::getSectionFields()) as $key) {
            $dbRow = $settings->get($key);
            $content = CurriculumSetting::getContent($key, $dbRow?->content);

            if ($dbRow && isset($mediaCollections[$key])) {
                $media = $this->imageService->getFirstMediaData($dbRow, $mediaCollections[$key]);
                if ($media) {
                    $content['image'] = $media;
                }
            }

            $curriculumData[$key] = $content;
        }

        return Inertia::render('KurikulumPage', [
            'programs' => $programs,
            'curriculumData' => $curriculumData
        ]);
    }

    /**
     * Display the academic calendar page
     */
    public function kalenderAkademik()
    {
        $calendars = AcademicCalendarContent::where('is_active', true)
            ->orderBy('academic_year_start', 'desc')
            ->orderBy('semester', 'asc')
            ->orderBy('sort_order', 'asc')
            ->with('media')
            ->get()
            ->map(function ($cal) {
                $data = $cal->toArray();
                $media = $this->imageService->getFirstMediaData($cal, 'calendar_images');
                if ($media) {
                    $data['image'] = $media;
                } elseif ($cal->calendar_image_url) {
                    $data['image_url'] = $cal->calendar_image_url;
                }
                return $data;
            });

        return Inertia::render('AcademicCalendarPage', [
            'calendars' => $calendars
        ]);
    }

    /**
     * Display the school programs page (non-Program Studi)
     */
    public function program()
    {
        // Ambil semua program KECUALI Program Studi
        $programsQuery = Program::where('category', '!=', 'Program Studi')
            ->orderBy('sort_order')
            ->with('media')
            ->get();

        // Transform programs to include responsive image data
        $programs = $programsQuery->map(function ($program) {
            $data = $program->toArray();
            $media = $this->imageService->getFirstMediaData($program, 'program_image');
            if ($media) {
                $data['image'] = $media; // Inject media object
            }
            return $data;
        });

        // Hero Program Settings
        $heroSetting = SiteSetting::where('section_key', 'hero_program')->first();
        $heroData = [];
        
        if ($heroSetting) {
            $content = $heroSetting->content;
            if (is_string($content)) {
                $heroData = json_decode($content, true) ?? [];
            } else {
                $heroData = $content ?? [];
            }
            
            // Inject Media
            $bgMedia = $this->imageService->getFirstMediaData($heroSetting, 'hero_program_bg');
            if ($bgMedia) {
                $heroData['backgroundImage'] = $bgMedia;
            }
        }

        return Inertia::render('ProgramSekolahPage', [
            'programs' => $programs,
            'heroSettings' => $heroData
        ]);
    }
}
