<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheService
{
    /**
     * Cache TTL configurations (in seconds)
     */
    protected static array $ttl = [
        'site_settings' => 3600,        // 1 hour
        'posts' => 900,                 // 15 minutes
        'popular_posts' => 1800,        // 30 minutes
        'programs' => 3600,             // 1 hour
        'galleries' => 1800,            // 30 minutes
        'teachers' => 3600,             // 1 hour
        'extracurriculars' => 3600,     // 1 hour
        'faqs' => 3600,                 // 1 hour
        'alumnis' => 3600,              // 1 hour
        'academic_calendars' => 3600,   // 1 hour
    ];

    /**
     * Get cached site settings or fetch and cache them
     */
    public static function getSiteSettings(string $key, callable $callback)
    {
        return Cache::remember("site_settings.{$key}", self::$ttl['site_settings'], $callback);
    }

    /**
     * Get cached posts or fetch and cache them
     */
    public static function getPosts(int $limit = 10, callable $callback)
    {
        return Cache::remember("posts.latest.{$limit}", self::$ttl['posts'], $callback);
    }

    /**
     * Get cached popular posts
     */
    public static function getPopularPosts(int $limit = 5, callable $callback)
    {
        return Cache::remember("posts.popular.{$limit}", self::$ttl['popular_posts'], $callback);
    }

    /**
     * Get cached programs
     */
    public static function getPrograms(callable $callback)
    {
        return Cache::remember('programs.all', self::$ttl['programs'], $callback);
    }

    /**
     * Get cached featured programs
     */
    public static function getFeaturedPrograms(callable $callback)
    {
        return Cache::remember('programs.featured', self::$ttl['programs'], $callback);
    }

    /**
     * Get cached galleries
     */
    public static function getGalleries(int $limit = 12, callable $callback)
    {
        return Cache::remember("galleries.latest.{$limit}", self::$ttl['galleries'], $callback);
    }

    /**
     * Get cached teachers
     */
    public static function getTeachers(callable $callback)
    {
        return Cache::remember('teachers.all', self::$ttl['teachers'], $callback);
    }

    /**
     * Get cached extracurriculars
     */
    public static function getExtracurriculars(callable $callback)
    {
        return Cache::remember('extracurriculars.all', self::$ttl['extracurriculars'], $callback);
    }

    /**
     * Get cached FAQs
     */
    public static function getFaqs(callable $callback)
    {
        return Cache::remember('faqs.all', self::$ttl['faqs'], $callback);
    }

    /**
     * Get cached alumni
     */
    public static function getAlumni(callable $callback)
    {
        return Cache::remember('alumni.all', self::$ttl['alumnis'], $callback);
    }

    /**
     * Get cached academic calendars
     */
    public static function getAcademicCalendars(callable $callback)
    {
        return Cache::remember('academic_calendars.all', self::$ttl['academic_calendars'], $callback);
    }

    /**
     * Invalidate all caches related to a specific tag
     */
    public static function invalidate(string $tag): void
    {
        Cache::tags([$tag])->flush();
    }

    /**
     * Invalidate all caches
     */
    public static function invalidateAll(): void
    {
        Cache::flush();
    }

    /**
     * Invalidate posts cache
     */
    public static function invalidatePosts(): void
    {
        Cache::tags(['posts'])->flush();
    }

    /**
     * Invalidate programs cache
     */
    public static function invalidatePrograms(): void
    {
        Cache::tags(['programs'])->flush();
    }

    /**
     * Invalidate galleries cache
     */
    public static function invalidateGalleries(): void
    {
        Cache::tags(['galleries'])->flush();
    }

    /**
     * Invalidate teachers cache
     */
    public static function invalidateTeachers(): void
    {
        Cache::tags(['teachers'])->flush();
    }

    /**
     * Invalidate extracurriculars cache
     */
    public static function invalidateExtracurriculars(): void
    {
        Cache::tags(['extracurriculars'])->flush();
    }

    /**
     * Invalidate FAQs cache
     */
    public static function invalidateFaqs(): void
    {
        Cache::tags(['faqs'])->flush();
    }

    /**
     * Invalidate alumni cache
     */
    public static function invalidateAlumni(): void
    {
        Cache::tags(['alumni'])->flush();
    }

    /**
     * Invalidate academic calendars cache
     */
    public static function invalidateAcademicCalendars(): void
    {
        Cache::tags(['academic_calendars'])->flush();
    }

    /**
     * Remember with tags for better cache management
     */
    public static function rememberWithTags(array $tags, string $key, int $ttl, callable $callback)
    {
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    /**
     * Get cache statistics for monitoring
     */
    public static function getStats(): array
    {
        $redis = null;
        try {
            if (class_exists('Illuminate\Support\Facades\Redis')) {
                $redis = \Illuminate\Support\Facades\Redis::connection('default');
            }
        } catch (\Exception $e) {
            // Redis not available
        }

        return [
            'driver' => config('cache.default'),
            'stores' => array_keys(config('cache.stores')),
            'redis_available' => $redis !== null,
            'ttl_configurations' => self::$ttl,
        ];
    }

    /**
     * Warm up all caches (call this after deployment or via scheduler)
     */
    public static function warmUp(): void
    {
        // Warm up site settings
        \App\Models\SiteSetting::getCachedAll();

        // Warm up posts
        \App\Models\Post::where('status', 'published')
            ->latest('published_at')
            ->take(10)
            ->get();

        // Warm up featured programs
        \App\Models\Program::where('is_featured', true)
            ->orderBy('sort_order')
            ->get();

        // Warm up galleries
        \App\Models\Gallery::latest()->take(12)->get();

        // Warm up active teachers
        \App\Models\Teacher::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Warm up active extracurriculars
        \App\Models\Extracurricular::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Warm up FAQs
        \App\Models\Faq::where('is_published', true)
            ->orderBy('sort_order')
            ->get();

        // Warm up published alumni
        \App\Models\Alumni::where('is_published', true)
            ->orderBy('sort_order')
            ->get();

        // Warm up active academic calendars
        \App\Models\AcademicCalendarContent::where('is_active', true)
            ->orderBy('academic_year_start', 'desc')
            ->get();
    }
}