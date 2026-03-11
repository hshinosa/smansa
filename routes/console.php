<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Jobs\TestQueueJob;
use Illuminate\Support\Facades\DB;

Artisan::command('queue:stress-test {count=10} {--fail=0}', function () {
    $count = (int) $this->argument('count');
    $failCount = (int) $this->option('fail');

    $this->info("Dispatching {$count} jobs to the queue...");

    $start = microtime(true);

    for ($i = 1; $i <= $count; $i++) {
        $shouldFail = ($i <= $failCount);
        TestQueueJob::dispatch($i, $shouldFail);
    }

    $end = microtime(true);
    $duration = $end - $start;

    $this->info("Successfully dispatched {$count} jobs in " . number_format($duration, 4) . " seconds.");
    
    $jobCount = DB::table('jobs')->count();
    $this->info("Current jobs in 'jobs' table: {$jobCount}");
    
    $failedJobCount = DB::table('failed_jobs')->count();
    $this->info("Current jobs in 'failed_jobs' table: {$failedJobCount}");
    
    $this->info("Run 'php artisan queue:work' to process these jobs.");
})->purpose('Run a stress test on the queue system');

/*
|--------------------------------------------------------------------------
| Instagram Scraper & AI News Generator Schedule
|--------------------------------------------------------------------------
|
| Automated workflow to scrape Instagram feeds and generate news articles:
| 1. Daily at 6 AM: Scrape Instagram for new posts
| 2. Daily at 7 AM: Process scraped feeds with AI to create draft articles
| 3. Hourly: Process any pending feeds (in case of failures)
|
*/

// Daily Instagram Scraping at 6:00 AM (Apify)
Schedule::command('instagram:scrape sman1baleendah --max-posts=10 --apify')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('[Cron] Instagram scraping completed successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('[Cron] Instagram scraping failed');
    });

// Nonaktifkan pemrosesan otomatis. Admin harus melakukan audit manual/AI melalui dashbord.
// Schedule::command('instagram:process --limit=10')
//     ->dailyAt('07:00')
//     ->withoutOverlapping()
//     ->onSuccess(function () {
//         \Illuminate\Support\Facades\Log::info('[Cron] Instagram feed processing completed');
//     })
//     ->onFailure(function () {
//         \Illuminate\Support\Facades\Log::error('[Cron] Instagram feed processing failed');
//     });

// Hourly processing juga dinonaktifkan untuk mematuhi audit admin
// Schedule::command('instagram:process --limit=5')
//     ->hourly()
//     ->withoutOverlapping()
//     ->when(function () {
//         // Only run if there are pending feeds
//         try {
//             $pendingCount = \Illuminate\Support\Facades\DB::table('sc_raw_news_feeds')
//                 ->where('is_processed', false)
//                 ->whereNull('error_message')
//                 ->count();
//             return $pendingCount > 0;
//         } catch (\Exception $e) {
//             return false;
//         }
//     });

// Weekly cleanup of old processed feeds (every Sunday at 2 AM)
Schedule::call(function () {
    try {
        $deleted = \Illuminate\Support\Facades\DB::table('sc_raw_news_feeds')
            ->where('is_processed', true)
            ->where('processed_at', '<', now()->subDays(30))
            ->delete();
        
        \Illuminate\Support\Facades\Log::info('[Cron] Cleaned up old Instagram feeds', ['deleted' => $deleted]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('[Cron] Cleanup failed', ['error' => $e->getMessage()]);
    }
})->weeklyOn(0, '02:00'); // Sunday at 2 AM

// Daily cleanup of old activity logs (every day at 3 AM)
// Keeps only the last 30 days of logs to prevent DB bloat
Schedule::call(function () {
    try {
        $deleted = \App\Models\ActivityLog::where('created_at', '<', now()->subDays(30))->delete();
        \Illuminate\Support\Facades\Log::info('[Cron] Activity logs pruning completed', ['deleted' => $deleted]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('[Cron] Activity logs pruning failed', ['error' => $e->getMessage()]);
    }
})->dailyAt('03:00');

// Cleanup stuck processing posts every 15 minutes
// Posts stuck in processing_status for more than 10 minutes are reset
Schedule::command('instagram:cleanup-stuck --minutes=10')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('[Cron] Stuck post cleanup completed');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::warning('[Cron] Stuck post cleanup failed');
    });

