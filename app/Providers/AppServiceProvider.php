<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\SiteSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Console\Commands\RagReindexDbContent;
use App\Console\Commands\RagEvaluateRetrieval;
use App\Observers\CacheInvalidationObserver;
use App\Models\Post;
use App\Models\Gallery;
use App\Models\Teacher;
use App\Models\Extracurricular;
use App\Models\Faq;
use App\Models\Alumni;
use App\Models\AcademicCalendarContent;
use App\Models\Program;
use App\Models\LandingPageSetting;
use App\Models\SpmbSetting;
use App\Models\SchoolProfileSetting;
use App\Models\ProgramStudiSetting;
use App\Models\CurriculumSetting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RagReindexDbContent::class,
                RagEvaluateRetrieval::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define rate limiter for AI Chat
        RateLimiter::for('ai_chat', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())->response(function (Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak permintaan. Silakan coba lagi dalam satu menit.',
                    'type' => 'error'
                ], 429, $headers);
            });
        });

        // Define rate limiter for Security Reports (Strict)
        RateLimiter::for('security_report', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Enable method spoofing for legacy HTML forms and some XHR clients
        \Illuminate\Http\Request::enableHttpMethodParameterOverride();

        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
        }

        Vite::prefetch(concurrency: 3);
        Schema::defaultStringLength(191);

        // Register CacheInvalidationObserver for automatic cache clearing
        $cacheObserver = CacheInvalidationObserver::class;
        Post::observe($cacheObserver);
        Gallery::observe($cacheObserver);
        Teacher::observe($cacheObserver);
        Extracurricular::observe($cacheObserver);
        Faq::observe($cacheObserver);
        Alumni::observe($cacheObserver);
        AcademicCalendarContent::observe($cacheObserver);
        Program::observe($cacheObserver);
        SiteSetting::observe($cacheObserver);
        LandingPageSetting::observe($cacheObserver);
        SpmbSetting::observe($cacheObserver);
        SchoolProfileSetting::observe($cacheObserver);
        ProgramStudiSetting::observe($cacheObserver);
        CurriculumSetting::observe($cacheObserver);

        // Share site settings with all views using cache
        try {
            if (!app()->runningInConsole() && Schema::hasTable('site_settings')) {
                // Cache site settings for performance
                $settings = cache()->remember('site_settings_all', 3600, function () {
                    return SiteSetting::getCachedAll();
                });
                View::share('siteSettings', $settings);
            }
        } catch (\Exception $e) {
            // Silently fail if DB is not ready (e.g. during build)
        }
    }
}
