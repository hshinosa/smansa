<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunInstagramScraperJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 1;

    protected string $username;
    protected int $maxPosts;
    protected int $logId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $username, int $maxPosts, int $logId)
    {
        $this->username = $username;
        $this->maxPosts = $maxPosts;
        $this->logId = $logId;
        
        $this->onQueue('instagram');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[ScraperJob] Starting background scraping', [
            'username' => $this->username,
            'log_id' => $this->logId
        ]);

        try {
            $exitCode = Artisan::call('instagram:scrape', [
                'target' => $this->username,
                '--max-posts' => $this->maxPosts,
                '--apify' => true,
            ]);

            if ($exitCode === 0) {
                // Get stats from the latest posts
                $postsFound = DB::table('sc_raw_news_feeds')
                    ->where('source_username', $this->username)
                    ->orderBy('scraped_at', 'desc')
                    ->limit($this->maxPosts)
                    ->count();

                DB::table('sc_scraper_logs')
                    ->where('id', $this->logId)
                    ->update([
                        'status' => 'completed',
                        'posts_found' => $postsFound,
                        'posts_saved' => $postsFound,
                        'message' => "Berhasil menyimpan {$postsFound} post",
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);

                Log::info('[ScraperJob] Background scraping completed', [
                    'username' => $this->username,
                    'posts_saved' => $postsFound,
                ]);
            } else {
                $output = Artisan::output();
                
                DB::table('sc_scraper_logs')
                    ->where('id', $this->logId)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $output,
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);

                Log::error('[ScraperJob] Background scraping failed', [
                    'username' => $this->username,
                    'output' => $output
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[ScraperJob] Exception during scraping', [
                'username' => $this->username,
                'error' => $e->getMessage()
            ]);

            DB::table('sc_scraper_logs')
                ->where('id', $this->logId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }
}
