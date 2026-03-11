<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\Gallery;
use App\Models\Teacher;
use App\Models\Extracurricular;
use App\Models\Faq;
use App\Models\Alumni;
use App\Models\AcademicCalendarContent;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Models\LandingPageSetting;
use App\Models\SpmbSetting;
use App\Models\SchoolProfileSetting;
use App\Models\ProgramStudiSetting;
use App\Models\CurriculumSetting;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

/**
 * Observer for automatic cache invalidation on model changes
 */
class CacheInvalidationObserver
{
    /**
     * Handle the created event - clear relevant caches
     */
    public function created($model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the updated event - clear relevant caches
     */
    public function updated($model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the deleted event - clear relevant caches
     */
    public function deleted($model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Invalidate cache based on model type
     */
    protected function invalidateCache($model): void
    {
        $modelClass = get_class($model);

        match ($modelClass) {
            Post::class => $this->invalidatePostCache(),
            Gallery::class => $this->invalidateGalleryCache(),
            Teacher::class => $this->invalidateTeacherCache(),
            Extracurricular::class => $this->invalidateExtracurricularCache(),
            Faq::class => $this->invalidateFaqCache(),
            Alumni::class => $this->invalidateAlumniCache(),
            AcademicCalendarContent::class => $this->invalidateAcademicCalendarCache(),
            Program::class => $this->invalidateProgramCache(),
            SiteSetting::class => $this->invalidateSiteSettingCache(),
            LandingPageSetting::class => $this->invalidateLandingPageCache(),
            SpmbSetting::class => $this->invalidateSpmbCache(),
            SchoolProfileSetting::class => $this->invalidateSchoolProfileCache(),
            ProgramStudiSetting::class => $this->invalidateProgramStudiCache(),
            CurriculumSetting::class => $this->invalidateCurriculumCache(),
            default => null,
        };
    }

    /**
     * Invalidate post-related caches
     */
    protected function invalidatePostCache(): void
    {
        Cache::forget('posts.latest.*');
        Cache::forget('posts.popular.*');
        Cache::forget('landing_page_data');
        
        // Clear tagged cache if using Redis
        try {
            Cache::tags(['posts'])->flush();
        } catch (\Exception $e) {
            // Tags not supported by current cache driver
        }
    }

    /**
     * Invalidate gallery-related caches
     */
    protected function invalidateGalleryCache(): void
    {
        Cache::forget('galleries.latest.*');
        Cache::forget('landing_page_data');
        
        try {
            Cache::tags(['galleries'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate teacher-related caches
     */
    protected function invalidateTeacherCache(): void
    {
        Cache::forget('teachers.all');
        
        try {
            Cache::tags(['teachers'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate extracurricular-related caches
     */
    protected function invalidateExtracurricularCache(): void
    {
        Cache::forget('extracurriculars.all');
        
        try {
            Cache::tags(['extracurriculars'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate FAQ-related caches
     */
    protected function invalidateFaqCache(): void
    {
        Cache::forget('faqs.all');
        
        try {
            Cache::tags(['faqs'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate alumni-related caches
     */
    protected function invalidateAlumniCache(): void
    {
        Cache::forget('alumni.all');
        
        try {
            Cache::tags(['alumni'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate academic calendar-related caches
     */
    protected function invalidateAcademicCalendarCache(): void
    {
        Cache::forget('academic_calendars.all');
        
        try {
            Cache::tags(['academic_calendars'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate program-related caches
     */
    protected function invalidateProgramCache(): void
    {
        Cache::forget('programs.all');
        Cache::forget('programs.featured');
        Cache::forget('landing_page_data');
        
        try {
            Cache::tags(['programs'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate site setting caches
     */
    protected function invalidateSiteSettingCache(): void
    {
        Cache::forget('site_settings.*');
        Cache::forget('landing_page_data');
        
        try {
            Cache::tags(['site_settings'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate landing page caches
     */
    protected function invalidateLandingPageCache(): void
    {
        Cache::forget('landing_page_data');
        
        try {
            Cache::tags(['landing_page'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate SPMB caches
     */
    protected function invalidateSpmbCache(): void
    {
        Cache::forget('spmb_data');
        
        try {
            Cache::tags(['spmb'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate school profile caches
     */
    protected function invalidateSchoolProfileCache(): void
    {
        Cache::forget('school_profile.*');
        
        try {
            Cache::tags(['school_profile'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate program studi caches
     */
    protected function invalidateProgramStudiCache(): void
    {
        Cache::forget('program_studi.*');
        Cache::forget('landing_page_data');
        
        try {
            Cache::tags(['program_studi'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    /**
     * Invalidate curriculum caches
     */
    protected function invalidateCurriculumCache(): void
    {
        Cache::forget('curriculum.*');
        
        try {
            Cache::tags(['curriculum'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
