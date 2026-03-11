<?php

namespace App\Providers;

use App\Models\AcademicCalendarContent;
use App\Models\Alumni;
use App\Models\CurriculumSetting;
use App\Models\Extracurricular;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\LandingPageSetting;
use App\Models\Post;
use App\Models\Program;
use App\Models\ProgramStudiSetting;
use App\Models\PtnAdmission;
use App\Models\SchoolProfileSetting;
use App\Models\SiteSetting;
use App\Models\SpmbSetting;
use App\Models\Teacher;
use App\Models\TkaAverage;
use App\Observers\DatabaseContentRagObserver;
use Illuminate\Support\ServiceProvider;

class RagSyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Post::observe(DatabaseContentRagObserver::class);
        Faq::observe(DatabaseContentRagObserver::class);
        Program::observe(DatabaseContentRagObserver::class);
        Extracurricular::observe(DatabaseContentRagObserver::class);
        Teacher::observe(DatabaseContentRagObserver::class);
        Alumni::observe(DatabaseContentRagObserver::class);
        Gallery::observe(DatabaseContentRagObserver::class);
        AcademicCalendarContent::observe(DatabaseContentRagObserver::class);
        LandingPageSetting::observe(DatabaseContentRagObserver::class);
        CurriculumSetting::observe(DatabaseContentRagObserver::class);
        SchoolProfileSetting::observe(DatabaseContentRagObserver::class);
        SpmbSetting::observe(DatabaseContentRagObserver::class);
        ProgramStudiSetting::observe(DatabaseContentRagObserver::class);
        PtnAdmission::observe(DatabaseContentRagObserver::class);
        TkaAverage::observe(DatabaseContentRagObserver::class);
        SiteSetting::observe(DatabaseContentRagObserver::class);
    }
}
