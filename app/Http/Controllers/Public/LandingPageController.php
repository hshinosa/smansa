<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\LandingPageSetting;
use App\Models\Post;
use App\Models\Program;
use App\Services\ImageService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

/**
 * Controller for public landing page
 * Refactored from routes/web.php closure
 */
class LandingPageController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display the landing page
     */
    public function index()
    {
        // Cache landing page data for 30 minutes
        return Cache::remember('landing_page_data', 60 * 30, function () {
            $settings = LandingPageSetting::with('media')->get()->keyBy('section_key');

            // Get thumbnail images from Program Studi Settings
            $programStudiThumbnails = $this->imageService->getProgramStudiThumbnails();

            // Filter hanya Program Studi untuk Landing Page
            $featuredPrograms = $this->getFeaturedPrograms($programStudiThumbnails);

            $featuredGalleries = Gallery::where('is_featured', true)->latest()->get();

            // Helper untuk mendapatkan konten atau default
            $getContentOrDefault = function ($key, $defaults) use ($settings) {
                $dbRow = $settings->get($key);
                $content = $dbRow?->content;

                // Handle JSON string or array
                $finalContent = $defaults;
                if (is_string($content)) {
                    $decoded = json_decode($content, true);
                    $finalContent = is_array($decoded) ? $decoded : $defaults;
                } elseif (is_array($content)) {
                    $finalContent = $content;
                }

                // Inject Media Library Data (WebP conversions)
                if ($dbRow) {
                    if ($key === 'hero') {
                        $bgMedia = $this->imageService->getFirstMediaData($dbRow, 'hero_background');
                        if ($bgMedia) {
                            $finalContent['backgroundImage'] = $bgMedia;
                        }

                        $studentMedia = $this->imageService->getFirstMediaData($dbRow, 'hero_student');
                        if ($studentMedia) {
                            $finalContent['studentImage'] = $studentMedia;
                        }
                    } elseif ($key === 'about_lp') {
                        $aboutMedia = $this->imageService->getFirstMediaData($dbRow, 'about_image');
                        if ($aboutMedia) {
                            $finalContent['aboutImage'] = $aboutMedia;
                        }
                    } elseif ($key === 'kepsek_welcome_lp') {
                        $kepsekMedia = $this->imageService->getFirstMediaData($dbRow, 'kepsek_image');
                        if ($kepsekMedia) {
                            $finalContent['kepsekImage'] = $kepsekMedia;
                        }
                    }
                }

                return $finalContent;
            };

            // Load default values from config
            $defaultHero = config('landing-page.hero');
            $defaultAbout = config('landing-page.about');
            $defaultKepsek = config('landing-page.kepsek');
            $defaultPrograms = config('landing-page.programs');
            $defaultGallery = config('landing-page.gallery');
            $defaultCta = config('landing-page.cta');

            $latestPosts = $this->getLatestPosts();

            // Gallery Images for Carousel (WebP preferred)
            $galleryImages = $this->getGalleryImages();

            return Inertia::render('LandingPage', [
                'heroContent' => $getContentOrDefault('hero', $defaultHero),
                'aboutLpContent' => $getContentOrDefault('about_lp', $defaultAbout),
                'kepsekWelcomeLpContent' => $getContentOrDefault('kepsek_welcome_lp', $defaultKepsek),
                'programsLpContent' => array_merge($getContentOrDefault('programs_lp', $defaultPrograms), ['items' => $featuredPrograms]),
                'galleryLpContent' => array_merge($getContentOrDefault('gallery_lp', $defaultGallery), ['images' => $galleryImages]),
                'ctaLpContent' => $getContentOrDefault('cta_lp', $defaultCta),
                'latestPosts' => $latestPosts,
            ]);
        });
    }

    /**
     * Get featured programs with thumbnails
     */
    protected function getFeaturedPrograms(array $programStudiThumbnails): array
    {
        return $this->imageService->mapProgramsWithThumbnails(
            Program::where('is_featured', true)
                ->where('category', 'Program Studi')
                ->orderBy('sort_order')
                ->with('media')
                ->get(),
            $programStudiThumbnails
        );
    }

    /**
     * Get latest posts for landing page
     */
    protected function getLatestPosts(): array
    {
        $posts = Post::where('status', 'published')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->take(3)
            ->with('media')
            ->get();

        return $this->imageService->transformPostsCollection($posts)->toArray();
    }

    /**
     * Get gallery images for carousel
     */
    protected function getGalleryImages(): array
    {
        return Gallery::where('type', 'photo')
            ->latest()
            ->take(12)
            ->with('media')
            ->get()
            ->map(function ($gallery) {
                $media = $this->imageService->getFirstMediaData($gallery, 'images');

                return $media ? ($media['conversions']['webp'] ?? $media['original_url']) : $gallery->url;
            })
            ->values()
            ->toArray();
    }
}
