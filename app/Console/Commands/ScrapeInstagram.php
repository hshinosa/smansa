<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ScrapeInstagram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instagram:scrape 
                            {target? : Instagram username to scrape (optional, uses default if not provided)}
                            {--max-posts=20 : Maximum number of posts to scrape}
                            {--apify : Use Apify API instead of Python scraper}
                            {--apify-token= : Apify API token (optional, uses .env if not provided)}
                            {--python=python : Python executable path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Instagram scraper to fetch news feeds (Python or Apify API)';

    /**
     * Default target accounts to scrape (can be configured)
     */
    protected array $defaultTargets = [
        'sman1baleendah',     // Official school account
        // Add more target accounts here
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🤖 Starting Instagram Scraper...');
        $this->newLine();

        $useApify = $this->option('apify');

        // Check requirements based on scraping method
        if (!$useApify && !$this->checkBotAccountConfigured()) {
            $this->error('❌ No active bot account found!');
            $this->warn('Please configure a bot account first or use --apify flag');
            return Command::FAILURE;
        }

        // Get target username(s)
        $target = $this->argument('target');
        $targets = $target ? [$target] : $this->defaultTargets;
        $maxPosts = $this->option('max-posts');

        $successCount = 0;
        $failCount = 0;

        foreach ($targets as $targetUsername) {
            $this->info("📸 Scraping @{$targetUsername}...");
            
            try {
                if ($useApify) {
                    $apifyToken = $this->option('apify-token') ?: env('APIFY_API_TOKEN');
                    $result = $this->scrapeWithApify($targetUsername, $maxPosts, $apifyToken);
                } else {
                    $pythonPath = $this->option('python');
                    $result = $this->runPythonScraper($targetUsername, $maxPosts, $pythonPath);
                }
                
                if ($result['success']) {
                    $this->info("   ✅ Success: {$result['message']}");
                    $successCount++;
                } else {
                    $this->error("   ❌ Failed: {$result['message']}");
                    $failCount++;
                }
                
                $this->newLine();
                
            } catch (\Exception $e) {
                $this->error("   ❌ Error: {$e->getMessage()}");
                Log::error('[ScrapeInstagram] Scraping failed', [
                    'target' => $targetUsername,
                    'error' => $e->getMessage(),
                ]);
                $failCount++;
                $this->newLine();
            }
        }

        // Summary
        $this->info("📊 Scraping Summary:");
        $this->line("   ✅ Success: {$successCount}");
        $this->line("   ❌ Failed: {$failCount}");
        $this->newLine();

        // Show pending feeds
        $this->showPendingFeeds();

        return $successCount > 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Check if bot account is configured in database
     */
    protected function checkBotAccountConfigured(): bool
    {
        try {
            $activeAccount = DB::table('sc_bot_accounts')
                ->where('is_active', true)
                ->first();

            return $activeAccount !== null && 
                   $activeAccount->username !== 'CHANGE_ME';
        } catch (\Exception $e) {
            // Table might not exist yet
            return false;
        }
    }

    /**
     * Run Python scraper script
     */
    protected function runPythonScraper(string $target, int $maxPosts, string $pythonPath): array
    {
        $scraperDir = base_path('instagram-scraper');
        $scraperScript = $scraperDir . '/scraper.py';

        // Check if scraper script exists
        if (!file_exists($scraperScript)) {
            throw new \Exception("Scraper script not found: {$scraperScript}");
        }

        // Build command
        $command = [
            $pythonPath,
            $scraperScript,
            '--target', $target,
            '--max-posts', (string) $maxPosts,
        ];

        // Create process
        $process = new Process($command, $scraperDir, null, null, 600); // 10 min timeout

        // Run process
        $process->run();

        // Check if successful
        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput() ?: $process->getOutput();
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();

        // Parse output to extract statistics
        // Look for patterns like "Successfully scraped: X"
        if (preg_match('/Successfully scraped:\s*(\d+)/', $output, $matches)) {
            $scrapedCount = (int) $matches[1];
            return [
                'success' => true,
                'message' => "Scraped {$scrapedCount} new posts",
                'output' => $output,
            ];
        }

        return [
            'success' => true,
            'message' => 'Scraping completed',
            'output' => $output,
        ];
    }

    /**
     * Scrape Instagram using Apify API
     */
    protected function scrapeWithApify(string $username, int $maxPosts, ?string $apiToken): array
    {
        // Get API token from database if not provided
        if (!$apiToken) {
            $apiToken = DB::table('settings')
                ->where('key', 'apify_api_token')
                ->value('value');
        }

        if (!$apiToken) {
            throw new \Exception('Apify API token not found. Please set it in Admin Panel → Instagram Settings');
        }

        $this->info("   🌐 Using Apify API...");

        try {
            $client = new \GuzzleHttp\Client();
            
            // Check if we have a saved run ID for quick testing
            $savedRunId = env('APIFY_RUN_ID');
            
            if ($savedRunId) {
                $this->info("   📦 Using saved run ID: {$savedRunId}");
                $runId = $savedRunId;
                $status = 'SUCCEEDED'; // Assume completed
            } else {
                // Start new Apify actor run (apify~instagram-scraper)
                $actorId = 'apify~instagram-scraper';
                
                $this->info("   🚀 Starting actor run for @{$username}...");
                
                // apify~instagram-scraper input format
                $profileUrl = "https://www.instagram.com/{$username}/";
                
                $response = $client->post("https://api.apify.com/v2/acts/{$actorId}/runs", [
                    'query' => ['token' => $apiToken],
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'addParentData' => false,
                        'directUrls' => [$profileUrl],
                        'resultsLimit' => $maxPosts,
                        'resultsType' => 'posts',
                        'searchLimit' => 1,
                        'searchType' => 'hashtag',
                    ],
                ]);

                $runData = json_decode($response->getBody()->getContents(), true);
                $runId = $runData['data']['id'] ?? null;

                if (!$runId) {
                    throw new \Exception('Failed to start Apify actor run');
                }

                $this->info("   ⏳ Run ID: {$runId} - Waiting for completion...");

                // Wait for run to complete
                $status = 'RUNNING';
                $attempts = 0;
                $maxAttempts = 60; // 5 minutes max (5 seconds per attempt)

                while ($status === 'RUNNING' && $attempts < $maxAttempts) {
                    sleep(5);
                    $attempts++;

                    $statusResponse = $client->get("https://api.apify.com/v2/actor-runs/{$runId}", [
                        'query' => ['token' => $apiToken],
                    ]);

                    $statusData = json_decode($statusResponse->getBody()->getContents(), true);
                    $status = $statusData['data']['status'] ?? 'FAILED';

                    if ($attempts % 6 === 0) { // Show progress every 30 seconds
                        $this->info("   ⏳ Still running... ({$attempts} attempts)");
                    }
                }

                if ($status !== 'SUCCEEDED') {
                    throw new \Exception("Apify run {$status} after {$attempts} attempts");
                }

                $this->info("   ✅ Run completed!");
            }

            $this->info("   📥 Fetching results...");

            // Get results from dataset
            $resultsResponse = $client->get("https://api.apify.com/v2/actor-runs/{$runId}/dataset/items", [
                'query' => ['token' => $apiToken],
            ]);

            $posts = json_decode($resultsResponse->getBody()->getContents(), true);

            // Debug: show response structure
            // Filter out error entries
            $validPosts = array_filter($posts, function($post) {
                return isset($post['shortCode']) && !isset($post['error']);
            });

            if (empty($validPosts)) {
                return [
                    'success' => false,
                    'message' => 'No valid posts found',
                ];
            }
            
            $posts = $validPosts;

            $this->info("   📦 Found " . count($posts) . " posts");

            // Process and save posts
            $savedCount = 0;
            foreach ($posts as $post) {
                $saved = $this->saveApifyPost($post, $username);
                if ($saved) {
                    $savedCount++;
                }
            }

            return [
                'success' => true,
                'message' => "Saved {$savedCount}/" . count($posts) . " posts",
            ];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            throw new \Exception("Apify API error: " . $e->getMessage() . " | Response: {$errorBody}");
        }
    }

    /**
     * Save a post from Apify API response
     */
    protected function saveApifyPost(array $post, string $username): bool
    {
        try {
            // Updated mappings based on Apify response (apify~instagram-scraper)
            // Fields: shortCode, caption, displayUrl, images (array), timestamp, likesCount, commentsCount
            $shortcode = $post['shortCode'] ?? $post['id'] ?? null;
            $caption = $post['caption'] ?? '';
            $timestamp = $post['timestamp'] ?? now();

            if (!$shortcode) {
                $this->warn("   ⚠️ Skipping post without shortcode");
                Log::warning('[ScrapeInstagram] Post without shortcode', ['post' => $post]);
                return false;
            }

            // Check if already exists
            $exists = DB::table('sc_raw_news_feeds')
                ->where('post_shortcode', $shortcode)
                ->exists();

            if ($exists) {
                $this->line("   ⏭️ Already exists: {$shortcode}");
                return false;
            }

            // Extract image URLs from Apify response
            $imageUrls = [];
            
            // Primary: images array from Apify
            if (isset($post['images']) && is_array($post['images'])) {
                $imageUrls = array_filter($post['images'], 'is_string');
            }
            
            // Fallback 1: displayUrl if no images array
            if (empty($imageUrls) && isset($post['displayUrl'])) {
                $imageUrls[] = $post['displayUrl'];
            }
            
            // Fallback 2: photos array
            if (empty($imageUrls) && isset($post['photos']) && is_array($post['photos'])) {
                $imageUrls = $post['photos'];
            }

            if (empty($imageUrls)) {
                $this->warn("   ⚠️ No images found for {$shortcode}");
                Log::warning('[ScrapeInstagram] No images in post', ['shortcode' => $shortcode]);
            }

            // Download images to local storage
            $savedImages = $this->downloadApifyImages($imageUrls, $username, $shortcode);

            if (empty($savedImages)) {
                $this->warn("   ⚠️ No images downloaded for {$shortcode}");
            }

            // Insert to database
            DB::table('sc_raw_news_feeds')->insert([
                'post_shortcode' => $shortcode,
                'source_username' => $username,
                'caption' => $caption,
                'image_paths' => json_encode($savedImages),
                'likes_count' => $post['likesCount'] ?? 0,
                'comments_count' => $post['commentsCount'] ?? 0,
                'scraped_at' => $timestamp,
                'is_processed' => false,
            ]);

            $this->info("   ✅ Saved: {$shortcode} (" . count($savedImages) . " images)");
            return true;

        } catch (\Exception $e) {
            $this->error("   ❌ Failed to save post: {$e->getMessage()}");
            Log::error('[ScrapeInstagram] Failed to save Apify post', [
                'post' => $post,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Download images from Apify URLs to local storage
     */
    protected function downloadApifyImages(array $imageUrls, string $username, string $shortcode): array
    {
        $savedPaths = [];
        $downloadDir = base_path("instagram-scraper/downloads/{$username}");
        $tempDir = storage_path("app/temp/instagram/{$shortcode}");

        // Create directories if they don't exist
        if (!file_exists($downloadDir)) {
            mkdir($downloadDir, 0755, true);
        }
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $client = new \GuzzleHttp\Client([
            'verify' => false,
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);

        try {
            foreach ($imageUrls as $index => $url) {
                $extension = 'jpg';
                $filename = "{$shortcode}_" . ($index + 1) . ".{$extension}";
                $tempPath = "{$tempDir}/{$filename}";
                $finalPath = "{$downloadDir}/{$filename}";

                // Retry logic for each image download (up to 3 times)
                $success = retry(3, function () use ($client, $url, $tempPath) {
                    $response = $client->get($url);
                    if ($response->getStatusCode() !== 200) {
                        throw new \Exception("HTTP status " . $response->getStatusCode());
                    }
                    file_put_contents($tempPath, $response->getBody()->getContents());
                }, 2000); // 2 seconds sleep between retries

                if ($success !== false) {
                    // Move from temp to final destination
                    rename($tempPath, $finalPath);
                    $relativePath = "/downloads/{$username}/{$filename}";
                    $savedPaths[] = $relativePath;
                }
            }

            // Cleanup temp directory
            $this->cleanupDir($tempDir);

            // If we didn't get ALL images, and we want strict atomicity,
            // we could fail here, but usually partial success in scraping is okay
            // as long as we have the files we tracked.
            
            return $savedPaths;

        } catch (\Exception $e) {
            $this->error("   ❌ Fatal error during image processing for {$shortcode}: {$e->getMessage()}");
            Log::error('[ScrapeInstagram] Atomic image download failed', [
                'shortcode' => $shortcode,
                'error' => $e->getMessage(),
            ]);
            
            // Cleanup: remove any partial files for this shortcode if atomicity is required
            foreach ($savedPaths as $relPath) {
                $absPath = base_path("instagram-scraper" . $relPath);
                if (file_exists($absPath)) {
                    unlink($absPath);
                }
            }
            $this->cleanupDir($tempDir);
            
            return []; // Return empty to indicate failure for this post
        }
    }

    /**
     * Helper to cleanup directory
     */
    protected function cleanupDir(string $dir): void
    {
        if (!file_exists($dir)) return;
        
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
        rmdir($dir);
    }

    /**
     * Show pending feeds waiting for AI processing
     */
    protected function showPendingFeeds(): void
    {
        try {
            $pendingCount = DB::table('sc_raw_news_feeds')
                ->where('is_processed', false)
                ->count();

            if ($pendingCount > 0) {
                $this->warn("⏳ {$pendingCount} feed(s) pending AI processing");
                $this->line("   Run: php artisan instagram:process");
            } else {
                $this->info("✅ No pending feeds");
            }
        } catch (\Exception $e) {
            // Ignore if table doesn't exist
        }
    }
}
