<?php

namespace App\Http\Middleware;

use App\Models\AiSetting;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $siteSettings = Schema::hasTable('site_settings') ? SiteSetting::getCachedAll() : [];

        $queueHealthBadge = [
            'status' => 'ok',
            'pending_jobs' => 0,
            'failed_jobs' => 0,
        ];

        if ($request->user('admin') && Schema::hasTable('jobs') && Schema::hasTable('failed_jobs')) {
            try {
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();
                $pendingThreshold = (int) AiSetting::get('queue_alert_pending_threshold', 100);
                $failedThreshold = (int) AiSetting::get('queue_alert_failed_threshold', 1);

                $queueHealthBadge = [
                    'status' => ($pendingJobs >= $pendingThreshold || $failedJobs >= $failedThreshold) ? 'alert' : 'ok',
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                ];
            } catch (\Throwable $e) {
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'admin' => $request->user('admin'),
            ],
            'siteSettings' => $siteSettings,
            'queueHealthBadge' => $queueHealthBadge,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
