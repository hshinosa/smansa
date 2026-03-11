<?php

use App\Http\Controllers\AcademicCalendarPublicController;
use App\Http\Controllers\Admin\AcademicCalendarController;
use App\Http\Controllers\Admin\CurriculumController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\CloudflareStatsController;
use App\Http\Controllers\Admin\LandingPageContentController;
use App\Http\Controllers\Admin\SpmbContentController;
use App\Http\Controllers\Public\AkademikController;
use App\Http\Controllers\Public\AlumniController;
use App\Http\Controllers\Public\BeritaController;
use App\Http\Controllers\Public\GaleriController;
use App\Http\Controllers\Public\GuruStaffController;
use App\Http\Controllers\Public\KontakController;
use App\Http\Controllers\Public\LandingPageController;
use App\Http\Controllers\Public\PrestasiController;
use App\Http\Controllers\Public\ProfilController;
use App\Http\Controllers\Public\ProgramStudiController;
use App\Http\Controllers\Public\SpmbController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ============================================================================
// PUBLIC ROUTES
// ============================================================================

// Landing Page
Route::get('/', [LandingPageController::class, 'index'])->name('home');

// SPMB (PPDB) Information
Route::get('/informasi-spmb', [SpmbController::class, 'index'])->name('informasi.spmb');

// Alumni
Route::get('/alumni', [AlumniController::class, 'index'])->name('alumni');

// School Profile
Route::get('/profil-sekolah', [ProfilController::class, 'profilSekolah'])->name('profil.sekolah');
Route::get('/visi-misi', [ProfilController::class, 'visiMisi'])->name('visi.misi');
Route::get('/struktur-organisasi', [ProfilController::class, 'strukturOrganisasi'])->name('struktur.organisasi');

// Academic Achievement
Route::get('/akademik/prestasi-akademik', [PrestasiController::class, 'index'])->name('prestasi.akademik');
Route::get('/akademik/prestasi-akademik/serapan-ptn', function () {
    return redirect()->route('prestasi.akademik', [], 301);
})->name('prestasi.serapan-ptn');
Route::get('/akademik/prestasi-akademik/hasil-tka', function () {
    return redirect()->route('prestasi.akademik', [], 301);
})->name('hasil.tka');

// Academic Pages
Route::get('/akademik/ekstrakurikuler', [AkademikController::class, 'ekstrakurikuler'])->name('akademik.ekstrakurikuler');
Route::get('/akademik/organisasi-ekstrakurikuler', [AkademikController::class, 'organisasiEkstrakurikuler'])->name('akademik.organisasi_ekstrakurikuler');
Route::get('/akademik/kurikulum', [AkademikController::class, 'kurikulum'])->name('akademik.kurikulum');
Route::get('/akademik/kalender-akademik', [AkademikController::class, 'kalenderAkademik'])->name('akademik.kalender');

// Program Studi
Route::get('/akademik/program-studi/mipa', [ProgramStudiController::class, 'mipa'])->name('akademik.program.mipa');
Route::get('/akademik/program-studi/ips', [ProgramStudiController::class, 'ips'])->name('akademik.program.ips');
Route::get('/akademik/program-studi/bahasa', [ProgramStudiController::class, 'bahasa'])->name('akademik.program.bahasa');

// Program Sekolah
Route::get('/program', [AkademikController::class, 'program'])->name('program.sekolah');

// Kalender Akademik (using existing controller)
Route::get('/kalender-akademik', [AcademicCalendarPublicController::class, 'index'])->name('kalender.akademik');

// News/Berita
Route::get('/berita-pengumuman', [BeritaController::class, 'index'])->name('berita.pengumuman');
Route::get('/berita/{slug}', [BeritaController::class, 'show'])->name('berita.detail');

// Contact
Route::get('/kontak', [KontakController::class, 'index'])->name('kontak');
Route::post('/kontak', [\App\Http\Controllers\ContactController::class, 'store'])
    ->middleware('throttle:3,1')
    ->name('kontak.store');

// Gallery
Route::get('/galeri', [GaleriController::class, 'index'])->name('galeri');

// Teachers & Staff
Route::get('/guru-staff', [GuruStaffController::class, 'index'])->name('guru.staff');

// Seragam (School Uniforms)
Route::get('/seragam', [\App\Http\Controllers\Public\SeragamController::class, 'index'])->name('seragam');

// Login redirect
Route::get('/login', function () {
    return redirect()->route('admin.login.form');
})->name('login');

// ============================================================================
// ADMIN ROUTES
// ============================================================================
Route::prefix('admin')->name('admin.')->group(function () {
    // Base route for /admin
    Route::get('/', function () {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('admin.login.form');
    })->name('index');

    // Login routes
    Route::get('/login', [AdminLoginController::class, 'create'])
        ->middleware('guest:admin')
        ->name('login.form');

    Route::post('/login', [AdminLoginController::class, 'store'])
        ->middleware(['guest:admin', 'throttle:5,1'])
        ->name('login.attempt');

    Route::post('/logout', [AdminLoginController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout');

    // Dashboard
    Route::get('/dashboard', function () {
        $unreadMessagesCount = \App\Models\ContactMessage::where('is_read', false)->count();
        
        return Inertia\Inertia::render('Admin/DashboardPage', [
            'admin' => Auth::guard('admin')->user(),
            'unreadMessagesCount' => $unreadMessagesCount,
        ]);
    })->middleware('auth:admin')->name('dashboard');

    // Protected Admin Routes
    Route::middleware('auth:admin')->group(function () {
        // API Routes
        Route::get('/api/activity-logs', [ActivityLogController::class, 'index'])->name('api.activitylogs.index');
        Route::get('/api/cloudflare-stats', [CloudflareStatsController::class, 'getUniqueVisitors'])->name('api.cloudflare.stats');
        Route::get('/api/cloudflare-chart-stats', [CloudflareStatsController::class, 'getVisitorStatsForChart'])->name('api.cloudflare.chart.stats');
        
        // Landing Page Content
        Route::get('/landing-page-content', [LandingPageContentController::class, 'index'])->name('landingpage.content.index');
        Route::post('/landing-page-content/update-all', [LandingPageContentController::class, 'storeOrUpdate'])->name('landingpage.content.update_all');

        // Academic Calendar
        Route::resource('academic-calendar', AcademicCalendarController::class)->except(['show']);
        Route::patch('/academic-calendar/{content}/set-active', [AcademicCalendarController::class, 'setActive'])->name('academic-calendar.set-active');

        // SPMB Content
        Route::get('/spmb-content', [SpmbContentController::class, 'index'])->name('spmb.index');
        Route::put('/spmb-content/update-all', [SpmbContentController::class, 'storeOrUpdate'])->name('spmb.update_all');

        // Program Studi
        Route::get('/program-studi', [\App\Http\Controllers\Admin\ProgramStudiController::class, 'index'])->name('program-studi.index');
        Route::post('/program-studi/update-all', [\App\Http\Controllers\Admin\ProgramStudiController::class, 'storeOrUpdate'])->name('program-studi.update_all');

        // Programs & Galleries
        Route::resource('programs', \App\Http\Controllers\Admin\ProgramController::class);
        Route::resource('galleries', \App\Http\Controllers\Admin\GalleryController::class);

        // Teachers
        Route::post('/teachers/update-settings', [\App\Http\Controllers\Admin\TeacherController::class, 'updateSettings'])->name('teachers.update_settings');
        Route::resource('teachers', \App\Http\Controllers\Admin\TeacherController::class);

        // Posts
        Route::resource('posts', \App\Http\Controllers\Admin\PostController::class);

        // Alumni
        Route::resource('alumni', \App\Http\Controllers\Admin\AlumniController::class)
            ->parameters(['alumni' => 'alumni']);

        // FAQs
        Route::post('/faqs/reorder', [\App\Http\Controllers\Admin\FaqController::class, 'reorder'])->name('faqs.reorder');
        Route::resource('faqs', \App\Http\Controllers\Admin\FaqController::class);

        // School Profile
        Route::get('/school-profile', [\App\Http\Controllers\Admin\SchoolProfileController::class, 'index'])->name('school-profile.index');
        Route::post('/school-profile', [\App\Http\Controllers\Admin\SchoolProfileController::class, 'update'])->name('school-profile.update');

        // Curriculum
        Route::get('/kurikulum', [CurriculumController::class, 'index'])->name('curriculum.index');
        Route::post('/kurikulum', [CurriculumController::class, 'update'])->name('curriculum.update');

        // Site Settings
        Route::get('/site-settings', [\App\Http\Controllers\Admin\SiteSettingController::class, 'index'])->name('site-settings.index');
        Route::post('/site-settings', [\App\Http\Controllers\Admin\SiteSettingController::class, 'update'])->name('site-settings.update');
        
        // Contact Messages
        Route::get('/contact-messages', [\App\Http\Controllers\Admin\ContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::get('/contact-messages/{contactMessage}', [\App\Http\Controllers\Admin\ContactMessageController::class, 'show'])->name('contact-messages.show');
        Route::delete('/contact-messages/{contactMessage}', [\App\Http\Controllers\Admin\ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

        // Extracurriculars
        Route::get('/extracurriculars', [\App\Http\Controllers\Admin\ExtracurricularController::class, 'index'])->name('extracurriculars.index');
        Route::post('/extracurriculars', [\App\Http\Controllers\Admin\ExtracurricularController::class, 'store'])->name('extracurriculars.store');
        Route::put('/extracurriculars/{extracurricular}', [\App\Http\Controllers\Admin\ExtracurricularController::class, 'update'])->name('extracurriculars.update');
        Route::delete('/extracurriculars/{extracurricular}', [\App\Http\Controllers\Admin\ExtracurricularController::class, 'destroy'])->name('extracurriculars.destroy');

        // RAG Documents
        Route::resource('rag-documents', \App\Http\Controllers\Admin\RagDocumentController::class);
        Route::post('/rag-documents/{ragDocument}/reprocess', [\App\Http\Controllers\Admin\RagDocumentController::class, 'reprocess'])->name('rag-documents.reprocess');

        // AI Settings
        Route::get('/ai-settings', [\App\Http\Controllers\Admin\AiSettingController::class, 'index'])->name('ai-settings.index');
        Route::post('/ai-settings', [\App\Http\Controllers\Admin\AiSettingController::class, 'update'])->name('ai-settings.update');
        Route::post('/ai-settings/apify-token', [\App\Http\Controllers\Admin\AiSettingController::class, 'updateApifyToken'])->name('ai-settings.apify-token.update');
        Route::get('/ai-settings/models', [\App\Http\Controllers\Admin\AiSettingController::class, 'models'])->name('ai-settings.models');

        // RAG DB Content Indexing
        Route::post('/ai-settings/reindex-db-content', function () {
            Artisan::queue('rag:reindex-db-content');
            return back()->with('success', 'Reindex database content dimulai di queue.');
        })->name('ai-settings.reindex-db-content');

        // Instagram Bot Management
        Route::get('/instagram-bots', [\App\Http\Controllers\Admin\InstagramBotAccountController::class, 'index'])->name('instagram-bots.index');
        Route::post('/instagram-settings', [\App\Http\Controllers\Admin\InstagramBotAccountController::class, 'updateSettings'])->name('instagram-settings.update');
        Route::post('/instagram-scraper/run', [\App\Http\Controllers\Admin\InstagramBotAccountController::class, 'runScraper'])->name('instagram-scraper.run');
        Route::post('/instagram-scraper/single-post', [\App\Http\Controllers\Admin\InstagramBotAccountController::class, 'scrapeSinglePost'])->name('instagram-scraper.single-post');
        Route::post('/instagram-posts/{id}/approve', [\App\Http\Controllers\Admin\InstagramBotAccountController::class, 'approvePost'])->name('instagram-posts.approve');
        Route::post('/instagram-posts/{id}/process-ai', [\App\Http\Controllers\Admin\InstagramBotAccountController::class, 'processWithAI'])->name('instagram-posts.process-ai');
        Route::post('/instagram-posts/{id}/reset-status', [\App\Http\Controllers\Admin\InstagramBotAccountController::class, 'resetProcessingStatus'])->name('instagram-posts.reset-status');
        Route::delete('/instagram-posts/{id}/reject', [\App\Http\Controllers\Admin\InstagramBotAccountController::class, 'rejectPost'])->name('instagram-posts.reject');
        Route::post('/instagram-posts/bulk-approve', [\App\Http\Controllers\Admin\InstagramBotAccountController::class, 'bulkApprove'])->name('instagram-posts.bulk-approve');
        Route::post('/instagram-posts/cleanup-stuck', [\App\Http\Controllers\Admin\InstagramBotAccountController::class, 'cleanupStuckPosts'])->name('instagram-posts.cleanup-stuck');

        // PTN Admissions Management
        Route::get('/ptn-admissions', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'index'])->name('ptn-admissions.index');
        Route::post('/ptn-admissions/batches', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'storeBatch'])->name('ptn-admissions.batches.store');
        Route::put('/ptn-admissions/batches/{batch}', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'updateBatch'])->name('ptn-admissions.batches.update');
        Route::delete('/ptn-admissions/batches/{batch}', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'destroyBatch'])->name('ptn-admissions.batches.destroy');
        Route::get('/ptn-admissions/batches/{batch}', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'showBatch'])->name('ptn-admissions.batches.show');
        Route::post('/ptn-admissions/batches/{batch}/admissions', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'storeAdmission'])->name('ptn-admissions.batches.admissions.store');
        Route::post('/ptn-admissions/batches/{batch}/bulk-import', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'bulkImport'])->name('ptn-admissions.batches.bulk-import');
        Route::post('/ptn-admissions/batches/{batch}/import-excel', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'importExcel'])->name('ptn-admissions.batches.import-excel');
        Route::get('/ptn-admissions/download-template', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'downloadTemplate'])->name('ptn-admissions.download-template');
        Route::put('/ptn-admissions/admissions/{admission}', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'updateAdmission'])->name('ptn-admissions.admissions.update');
        Route::delete('/ptn-admissions/admissions/{admission}', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'destroyAdmission'])->name('ptn-admissions.admissions.destroy');
        Route::post('/ptn-admissions/universities', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'storeUniversity'])->name('ptn-admissions.universities.store');
        Route::put('/ptn-admissions/universities/{university}', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'updateUniversity'])->name('ptn-admissions.universities.update');
        Route::delete('/ptn-admissions/universities/{university}', [\App\Http\Controllers\Admin\PtnAdmissionController::class, 'destroyUniversity'])->name('ptn-admissions.universities.destroy');

        // TKA Averages Management
        Route::delete('/tka-averages/group', [\App\Http\Controllers\Admin\TkaAverageController::class, 'destroyGroup'])->name('tka-averages.group.destroy');
        Route::resource('tka-averages', \App\Http\Controllers\Admin\TkaAverageController::class);

        // Seragam (School Uniforms) - Using modal-based approach
        Route::resource('seragam', \App\Http\Controllers\Admin\SeragamController::class)->except(['create', 'show', 'edit']);
    });
});

// ============================================================================
// API ROUTES FOR CHATBOT
// ============================================================================
Route::prefix('api')->name('api.')->group(function () {
    Route::post('/chat/send', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage'])
        ->middleware('throttle:ai_chat')
        ->name('chat.send');
    
    Route::get('/chat/history', [\App\Http\Controllers\Api\ChatController::class, 'getHistory'])
        ->name('chat.history');

    // Security: CSP Reporting
    Route::post('/security/csp-report', [\App\Http\Controllers\SecurityController::class, 'handleCspReport'])
        ->middleware('throttle:security_report')
        ->name('security.csp-report');
});

// ============================================================================
// INSTAGRAM SCRAPER IMAGES ROUTE
// ============================================================================
Route::get('/scraped-images/{path}', function ($path) {
    $fullPath = base_path('instagram-scraper/downloads/' . $path);
    
    if (!file_exists($fullPath)) {
        abort(404);
    }
    
    // Security: only allow image files
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions)) {
        abort(403);
    }
    
    return response()->file($fullPath);
})->where('path', '.*')->name('scraped-images');

// ============================================================================
// MEDIA LIBRARY PROXY ROUTE
// Workaround for PHP built-in server 403 error on filenames with parentheses
// ============================================================================
Route::get('/media/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    
    // Security: ensure path is within storage/app/public
    $realBase = realpath(storage_path('app/public'));
    $realPath = realpath($fullPath);
    
    if (!$realPath || !str_starts_with($realPath, $realBase)) {
        abort(404);
    }
    
    if (!file_exists($realPath)) {
        abort(404);
    }
    
    return response()->file($realPath);
})->where('path', '.*')->name('media.proxy');

// ============================================================================
// SEO ROUTES
// ============================================================================
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [\App\Http\Controllers\SitemapController::class, 'robots'])->name('robots');

// ============================================================================
// HEALTH CHECK ROUTE
// ============================================================================
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'index'])->name('health');
