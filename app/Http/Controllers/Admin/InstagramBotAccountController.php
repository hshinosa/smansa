<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessInstagramPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Post;

class InstagramBotAccountController extends Controller
{
    /**
     * Display Instagram Apify settings and scraper statistics
     */
    public function index()
    {
        // Get current API token from database
        $apifyToken = DB::table('settings')
            ->where('key', 'apify_api_token')
            ->value('value');

        // Get scraper statistics
        $stats = $this->getScraperStatistics();
        
        // Get scraper logs
        $logs = $this->getScraperLogs();

        // Get pending posts for approval with pagination (6 per page)
        $pendingPostsQuery = DB::table('sc_raw_news_feeds')
            ->where('is_processed', false)
            ->orderBy('scraped_at', 'desc');
            
        $pendingPostsPaginated = $pendingPostsQuery->paginate(6)->withQueryString();
        
        $pendingPosts = [
            'data' => collect($pendingPostsPaginated->items())->map(function($post) {
                // Parse image paths from JSON if stored as JSON
                $imagePaths = $post->image_paths;
                if (is_string($imagePaths)) {
                    $imagePaths = json_decode($imagePaths, true) ?? [];
                }
                
                // Get first image for preview - convert to web-accessible URL
                $firstImage = null;
                $fixedImagePaths = [];
                if (!empty($imagePaths) && is_array($imagePaths)) {
                    foreach ($imagePaths as $path) {
                        // Remove leading slash and "downloads/" prefix for route
                        $cleanPath = ltrim($path, '/\\');
                        $cleanPath = preg_replace('#^downloads/#', '', $cleanPath);
                        // Build URL using the scraped-images route
                        $fixedImagePaths[] = '/scraped-images/' . $cleanPath;
                    }
                    $firstImage = $fixedImagePaths[0] ?? null;
                }

                return [
                    'id' => $post->id,
                    'shortcode' => $post->post_shortcode,
                    'username' => $post->source_username,
                    'caption' => $post->caption,
                    'caption_preview' => Str::limit($post->caption, 150),
                    'image_paths' => $fixedImagePaths,
                    'first_image' => $firstImage,
                    'likes_count' => $post->likes_count ?? 0,
                    'comments_count' => $post->comments_count ?? 0,
                    'processing_status' => $post->processing_status,
                    'error_message' => $post->error_message,
                    'scraped_at' => Carbon::parse($post->scraped_at)->format('Y-m-d H:i:s'),
                    'scraped_ago' => Carbon::parse($post->scraped_at)->diffForHumans(),
                ];
            }),
            'links' => $pendingPostsPaginated->linkCollection()->toArray(),
            'meta' => [
                'current_page' => $pendingPostsPaginated->currentPage(),
                'last_page' => $pendingPostsPaginated->lastPage(),
                'total' => $pendingPostsPaginated->total(),
            ]
        ];

        return Inertia::render('Admin/InstagramSettings/Index', [
            'apifyToken' => $apifyToken,
            'statistics' => $stats,
            'logs' => $logs,
            'pendingPosts' => $pendingPosts,
            'activeTab' => request('tab', 'api-key')
        ]);
    }

    /**
     * Update Apify API token in database
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'apify_token' => 'required|string|min:20',
        ]);

        try {
            // Save to database settings table
            DB::table('settings')->updateOrInsert(
                ['key' => 'apify_api_token'],
                [
                    'value' => $request->apify_token,
                    'updated_at' => now(),
                ]
            );

            Log::info('[Instagram] Apify API token updated in database');

            return redirect()->route('admin.instagram-bots.index', ['tab' => 'api-key'])
                ->with('success', 'API key berhasil disimpan');
        } catch (\Exception $e) {
            Log::error('[Instagram] Failed to update API token', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menyimpan API key: ' . $e->getMessage());
        }
    }

    /**
     * Run Instagram scraper asynchronously
     */
    public function runScraper(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'max_posts' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            // Check if token is set
            $apiToken = DB::table('settings')
                ->where('key', 'apify_api_token')
                ->value('value');

            if (!$apiToken) {
                return back()->with('error', 'API token tidak ditemukan. Silakan atur API token terlebih dahulu.');
            }

            $username = $request->username ?? 'sman1baleendah';
            $maxPosts = $request->max_posts ?? 25;

            // Log the start as "running"
            $logId = DB::table('sc_scraper_logs')->insertGetId([
                'username' => $username,
                'status' => 'running',
                'message' => 'Scraper sedang mengantre di background...',
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Dispatch job to queue (instead of Artisan::call)
            \App\Jobs\RunInstagramScraperJob::dispatch($username, $maxPosts, $logId);

            Log::info('[Instagram] Scraper job dispatched', [
                'username' => $username,
                'log_id' => $logId,
            ]);

            return back()->with('success', 'Scraper telah dimulai di background. Pantau status pada tabel Scraper Logs.');
        } catch (\Exception $e) {
            Log::error('[Instagram] Failed to dispatch scraper job', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memulai scraper: ' . $e->getMessage());
        }
    }

    /**
     * Get scraper statistics from database
     */
    protected function getScraperStatistics(): array
    {
        try {
            $stats = [
                'total_scraped' => DB::table('sc_raw_news_feeds')->count(),
                'pending_processing' => DB::table('sc_raw_news_feeds')->where('is_processed', false)->count(),
                'processed' => DB::table('sc_raw_news_feeds')->where('is_processed', true)->count(),
                'errors' => DB::table('sc_raw_news_feeds')->whereNotNull('error_message')->count(),
            ];

            // Get scraping activity by source
            $bySource = DB::table('sc_raw_news_feeds')
                ->select('source_username', DB::raw('COUNT(*) as count'))
                ->groupBy('source_username')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($item) => [
                    'username' => $item->source_username,
                    'count' => $item->count,
                ]);

            $stats['by_source'] = $bySource->toArray();

            // Get recent activity
            $recentActivity = DB::table('sc_raw_news_feeds')
                ->select('source_username', 'scraped_at', 'is_processed')
                ->orderBy('scraped_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($item) => [
                    'username' => $item->source_username,
                    'time' => Carbon::parse($item->scraped_at)->diffForHumans(),
                    'status' => $item->is_processed ? 'Processed' : 'Pending',
                ]);

            $stats['recent_activity'] = $recentActivity->toArray();

            return $stats;
        } catch (\Exception $e) {
            return [
                'total_scraped' => 0,
                'pending_processing' => 0,
                'processed' => 0,
                'errors' => 0,
                'by_source' => [],
                'recent_activity' => [],
            ];
        }
    }

    /**
     * Get scraper logs
     */
    protected function getScraperLogs(): array
    {
        try {
            return DB::table('sc_scraper_logs')
                ->orderBy('started_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function($log) {
                    return [
                        'id' => $log->id,
                        'username' => $log->username,
                        'status' => $log->status,
                        'posts_found' => $log->posts_found,
                        'posts_saved' => $log->posts_saved,
                        'message' => $log->message,
                        'error_message' => $log->error_message,
                        'started_at' => Carbon::parse($log->started_at)->format('Y-m-d H:i:s'),
                        'duration' => $log->completed_at ? 
                            Carbon::parse($log->started_at)->diffInSeconds(Carbon::parse($log->completed_at)) . 's' : 'running...',
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get pending posts from Instagram scraper
     */
    protected function getPendingPosts(): array
    {
        try {
            return DB::table('sc_raw_news_feeds')
                ->where('is_processed', false)
                ->orderBy('scraped_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function($post) {
                    // Parse image paths from JSON if stored as JSON
                    $imagePaths = $post->image_paths;
                    if (is_string($imagePaths)) {
                        $imagePaths = json_decode($imagePaths, true) ?? [];
                    }
                    
                    // Get first image for preview - convert to web-accessible URL
                    $firstImage = null;
                    $fixedImagePaths = [];
                    if (!empty($imagePaths) && is_array($imagePaths)) {
                        foreach ($imagePaths as $path) {
                            // Remove leading slash and "downloads/" prefix for route
                            $cleanPath = ltrim($path, '/\\');
                            $cleanPath = preg_replace('#^downloads/#', '', $cleanPath);
                            // Build URL using the scraped-images route
                            $fixedImagePaths[] = '/scraped-images/' . $cleanPath;
                        }
                        $firstImage = $fixedImagePaths[0] ?? null;
                    }

                    return [
                        'id' => $post->id,
                        'shortcode' => $post->post_shortcode,
                        'username' => $post->source_username,
                        'caption' => $post->caption,
                        'caption_preview' => Str::limit($post->caption, 150),
                        'image_paths' => $fixedImagePaths,
                        'first_image' => $firstImage,
                        'likes_count' => $post->likes_count ?? 0,
                        'comments_count' => $post->comments_count ?? 0,
                        'processing_status' => $post->processing_status,
                        'error_message' => $post->error_message,
                        'scraped_at' => Carbon::parse($post->scraped_at)->format('Y-m-d H:i:s'),
                        'scraped_ago' => Carbon::parse($post->scraped_at)->diffForHumans(),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('[Instagram] Failed to get pending posts', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Approve scraped post as draft news (dispatched to queue)
     */
    public function approvePost(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:berita,pengumuman,prestasi,akademik,kegiatan',
        ]);

        try {
            // Get the scraped post
            $scrapedPost = DB::table('sc_raw_news_feeds')->find($id);

            if (!$scrapedPost) {
                return back()->with('error', 'Post tidak ditemukan');
            }

            if ($scrapedPost->is_processed) {
                return back()->with('error', 'Post sudah diproses sebelumnya');
            }

            Log::info('[Instagram] Starting manual approval (Sync)', ['id' => $id, 'title' => $request->title]);

            // Mark as processing first to avoid double clicks or race conditions
            DB::table('sc_raw_news_feeds')
                ->where('id', $id)
                ->update([
                    'processing_status' => 'processing',
                    'updated_at' => now(),
                ]);

            // Process synchronously for manual approval as requested
            $job = new ProcessInstagramPost(
                (int)$id,
                $request->title,
                $request->category,
                (int)Auth::id(),
                false // useAI = false
            );
            
            // Resolve dependencies and execute handle
            // This will handle creating the draft post AND updating sc_raw_news_feeds (is_processed=true, etc)
            app()->call([$job, 'handle']);

            Log::info('[Instagram] Post approved successfully (Sync)', [
                'scraped_id' => $id,
                'title' => $request->title,
            ]);

            return back()->with('success', "Post berhasil di-approve sebagai draft. Judul: {$request->title}");
        } catch (\Exception $e) {
            Log::error('[Instagram] Failed to queue post approval', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memproses post: ' . $e->getMessage());
        }
    }

    /**
     * Reject/delete scraped post
     */
    public function rejectPost($id)
    {
        try {
            $deleted = DB::table('sc_raw_news_feeds')
                ->where('id', $id)
                ->delete();

            if ($deleted) {
                Log::info('[Instagram] Post rejected/deleted', ['id' => $id]);
                return back()->with('success', 'Post berhasil dihapus');
            }

            return back()->with('error', 'Post tidak ditemukan');
        } catch (\Exception $e) {
            Log::error('[Instagram] Failed to reject post', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus post: ' . $e->getMessage());
        }
    }

    /**
     * Bulk approve posts as drafts (with automatic queue processing)
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
            'category' => 'required|string|in:berita,pengumuman,prestasi,akademik,kegiatan',
        ]);

        try {
            $queued = 0;
            $failed = 0;

            foreach ($request->ids as $id) {
                $scrapedPost = DB::table('sc_raw_news_feeds')
                    ->where('id', $id)
                    ->where('is_processed', false)
                    ->first();

                if (!$scrapedPost) {
                    $failed++;
                    continue;
                }

                // Create title from first line of caption or use shortcode
                $captionLines = explode("\n", $scrapedPost->caption ?? '');
                $title = trim($captionLines[0] ?? '') ?: "Instagram Post {$scrapedPost->post_shortcode}";
                $title = preg_replace('/#\w+/', '', $title); // Remove hashtags
                $title = Str::limit(trim($title), 200);

                // Mark as processing
                DB::table('sc_raw_news_feeds')
                    ->where('id', $id)
                    ->update([
                        'processing_status' => 'queued',
                        'updated_at' => now(),
                    ]);

                // Dispatch job to queue
                ProcessInstagramPost::dispatch(
                    $id,
                    $title,
                    $request->category,
                    Auth::id(),
                    false // useAI = false for bulk approve
                );

                $queued++;
            }

            // Spawn queue worker in background to process jobs
            if ($queued > 0) {
                $this->spawnQueueWorkerIfNeeded();
            }

            Log::info('[Instagram] Bulk approve queued with auto worker', [
                'queued' => $queued,
                'failed' => $failed,
            ]);

            if ($failed > 0) {
                return back()->with('warning', "{$queued} post ditambahkan ke antrian, {$failed} gagal.");
            }

            return back()->with('success', "{$queued} post ditambahkan ke antrian. Akan diproses otomatis.");
        } catch (\Exception $e) {
            Log::error('[Instagram] Bulk approve failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal bulk approve: ' . $e->getMessage());
        }
    }

    /**
     * Process scraped post with AI to generate news content (with automatic queue processing)
     */
    public function processWithAI(Request $request, $id)
    {
        // Log queue connection at start for debugging
        $queueConnection = config('queue.default');
        Log::info('[Instagram] ===== PROCESS WITH AI STARTED =====', [
            'post_id' => $id,
            'queue_connection' => $queueConnection,
            'user_id' => Auth::id(),
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            // Get the scraped post
            $scrapedPost = DB::table('sc_raw_news_feeds')->find($id);

            if (!$scrapedPost) {
                Log::warning('[Instagram] Post not found', ['id' => $id]);
                return back()->with('error', 'Post tidak ditemukan');
            }

            if ($scrapedPost->is_processed) {
                Log::warning('[Instagram] Post already processed', ['id' => $id]);
                return back()->with('error', 'Post sudah diproses sebelumnya');
            }

            // Check if already being processed (prevent double-click issues)
            if ($scrapedPost->processing_status && 
                $scrapedPost->updated_at && 
                now()->diffInMinutes($scrapedPost->updated_at) < 5) {
                Log::warning('[Instagram] Post already being processed', [
                    'id' => $id, 
                    'status' => $scrapedPost->processing_status,
                    'updated_at' => $scrapedPost->updated_at,
                ]);
                return back()->with('error', 'Post sedang dalam proses. Tunggu beberapa saat.');
            }

            // Create initial title from caption
            $captionLines = explode("\n", $scrapedPost->caption ?? '');
            $title = trim($captionLines[0] ?? '') ?: "Berita dari Instagram @{$scrapedPost->source_username}";
            $title = preg_replace('/#\w+/', '', $title); // Remove hashtags
            $title = Str::limit(trim($title), 200);

            Log::info('[Instagram] Dispatching job with auto-queue worker', [
                'id' => $id,
                'title' => Str::limit($title, 50),
            ]);

            // Mark as processing with timestamp
            DB::table('sc_raw_news_feeds')
                ->where('id', $id)
                ->update([
                    'processing_status' => 'queued_ai',
                    'error_message' => null, // Clear previous errors
                    'updated_at' => now(),
                ]);

            // Create job instance
            $job = new ProcessInstagramPost(
                $id,
                $title,
                'berita', // AI will detect category
                Auth::id(),
                true // useAI = true
            );
            
            // Dispatch job to queue
            dispatch($job);

            // Spawn queue worker in background to process jobs automatically
            // This is a fire-and-forget approach that starts a queue worker if needed
            $this->spawnQueueWorkerIfNeeded();

            Log::info('[Instagram] ===== JOB DISPATCHED WITH AUTO WORKER =====', [
                'scraped_id' => $id,
            ]);

            return back()->with('success', 'Post sedang diproses dengan AI. Halaman akan ter-refresh otomatis ketika selesai.');
        } catch (\Exception $e) {
            Log::error('[Instagram] ===== AI PROCESSING FAILED =====', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clear processing status on error
            DB::table('sc_raw_news_feeds')
                ->where('id', $id)
                ->update([
                    'processing_status' => null,
                    'error_message' => 'Dispatch failed: ' . $e->getMessage(),
                    'updated_at' => now(),
                ]);

            return back()->with('error', 'Gagal memproses dengan AI: ' . $e->getMessage());
        }
    }

    /**
     * Spawn a queue worker in background if there are pending jobs and no worker running
     */
    protected function spawnQueueWorkerIfNeeded(): void
    {
        try {
            // Check if there are pending jobs
            $pendingJobs = DB::table('jobs')->count();
            
            if ($pendingJobs === 0) {
                return; // No jobs to process
            }

            // Check if a queue worker process is already running
            // We'll use a simple cache flag with TTL
            $workerLockKey = 'queue_worker_running';
            $cache = \Illuminate\Support\Facades\Cache::store('file');
            
            // If worker is already flagged as running (within last 5 minutes), skip
            if ($cache->get($workerLockKey)) {
                Log::info('[QueueWorker] Worker already running, skipping spawn');
                return;
            }

            // Set flag that worker is starting (5 minute TTL)
            $cache->put($workerLockKey, true, 300);

            // Spawn queue worker in background
            $phpBinary = PHP_BINARY ?: 'php';
            $artisan = base_path('artisan');
            
            // Windows: use 'start /B' for background process
            // Linux/Mac: use '&' and nohup
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - use start /B for background
                $command = "start /B {$phpBinary} {$artisan} queue:work --once --quiet 2>&1";
                pclose(popen($command, 'r'));
            } else {
                // Linux/Mac - use nohup and &
                $command = "nohup {$phpBinary} {$artisan} queue:work --once --quiet > /dev/null 2>&1 &";
                exec($command);
            }

            Log::info('[QueueWorker] Background queue worker spawned', [
                'command' => $command,
                'pending_jobs' => $pendingJobs,
            ]);
        } catch (\Exception $e) {
            Log::warning('[QueueWorker] Failed to spawn background worker', [
                'error' => $e->getMessage(),
            ]);
            // Don't throw - this is best-effort
        }
    }

    /**
     * Reset stuck processing status for a post (allow retry)
     */
    public function resetProcessingStatus($id)
    {
        try {
            $updated = DB::table('sc_raw_news_feeds')
                ->where('id', $id)
                ->where('is_processed', false)
                ->update([
                    'processing_status' => null,
                    'error_message' => null,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                Log::info('[Instagram] Reset processing status for post', ['id' => $id]);
                return back()->with('success', 'Status processing direset. Post siap diproses ulang.');
            }

            return back()->with('error', 'Post tidak ditemukan atau sudah diproses.');
        } catch (\Exception $e) {
            Log::error('[Instagram] Failed to reset processing status', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal reset status: ' . $e->getMessage());
        }
    }

    /**
     * Scrape a single Instagram post by URL
     */
    public function scrapeSinglePost(Request $request)
    {
        $request->validate([
            'post_url' => 'required|string|url',
        ]);

        try {
            $url = $request->post_url;
            
            // Parse Instagram URL to get shortcode
            // Supports: instagram.com/p/SHORTCODE, instagram.com/reel/SHORTCODE, instagram.com/tv/SHORTCODE
            $shortcode = null;
            if (preg_match('#instagram\.com/(p|reel|tv)/([A-Za-z0-9_-]+)#', $url, $matches)) {
                $shortcode = $matches[2];
            }

            if (!$shortcode) {
                return back()->with('error', 'URL Instagram tidak valid. Gunakan format: https://www.instagram.com/p/SHORTCODE atau https://www.instagram.com/reel/SHORTCODE');
            }

            // Check if already scraped
            $exists = DB::table('sc_raw_news_feeds')
                ->where('post_shortcode', $shortcode)
                ->exists();

            if ($exists) {
                return back()->with('error', "Post dengan shortcode {$shortcode} sudah di-scrape sebelumnya.");
            }

            // Get API token
            $apiToken = DB::table('settings')
                ->where('key', 'apify_api_token')
                ->value('value');

            if (!$apiToken) {
                return back()->with('error', 'API token tidak ditemukan. Silakan atur API token terlebih dahulu.');
            }

            Log::info('[Instagram] Scraping single post', ['url' => $url, 'shortcode' => $shortcode]);

            // Call Apify to scrape single post
            $client = new \GuzzleHttp\Client();
            $actorId = 'apify~instagram-scraper';

            $response = $client->post("https://api.apify.com/v2/acts/{$actorId}/runs", [
                'query' => ['token' => $apiToken],
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'addParentData' => false,
                    'directUrls' => [$url],
                    'resultsLimit' => 1,
                    'resultsType' => 'posts',
                ],
            ]);

            $runData = json_decode($response->getBody()->getContents(), true);
            $runId = $runData['data']['id'] ?? null;

            if (!$runId) {
                throw new \Exception('Failed to start Apify actor run');
            }

            // Wait for run to complete (max 60 seconds)
            $status = 'RUNNING';
            $attempts = 0;
            $maxAttempts = 12; // 60 seconds max

            while ($status === 'RUNNING' && $attempts < $maxAttempts) {
                sleep(5);
                $attempts++;

                $statusResponse = $client->get("https://api.apify.com/v2/actor-runs/{$runId}", [
                    'query' => ['token' => $apiToken],
                ]);

                $statusData = json_decode($statusResponse->getBody()->getContents(), true);
                $status = $statusData['data']['status'] ?? 'FAILED';
            }

            if ($status !== 'SUCCEEDED') {
                throw new \Exception("Apify run {$status} after {$attempts} attempts");
            }

            // Get results
            $resultsResponse = $client->get("https://api.apify.com/v2/actor-runs/{$runId}/dataset/items", [
                'query' => ['token' => $apiToken],
            ]);

            $posts = json_decode($resultsResponse->getBody()->getContents(), true);

            if (empty($posts) || !isset($posts[0]['shortCode'])) {
                return back()->with('error', 'Gagal mengambil data post dari Instagram.');
            }

            $post = $posts[0];
            
            // Extract username from the post data or URL
            $username = $post['ownerUsername'] ?? 'unknown';
            $caption = $post['caption'] ?? '';
            $timestamp = $post['timestamp'] ?? now();

            // Extract image URLs
            $imageUrls = [];
            if (isset($post['images']) && is_array($post['images'])) {
                $imageUrls = array_filter($post['images'], 'is_string');
            }
            if (empty($imageUrls) && isset($post['displayUrl'])) {
                $imageUrls[] = $post['displayUrl'];
            }
            if (empty($imageUrls) && isset($post['photos']) && is_array($post['photos'])) {
                $imageUrls = $post['photos'];
            }

            // Download images
            $savedImages = $this->downloadImagesForPost($imageUrls, $username, $shortcode);

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
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('[Instagram] Single post scraped successfully', [
                'shortcode' => $shortcode,
                'username' => $username,
                'images' => count($savedImages),
            ]);

            return redirect()->route('admin.instagram-bots.index', ['tab' => 'pending'])
                ->with('success', "Post {$shortcode} dari @{$username} berhasil di-scrape! Silakan proses dengan AI.");

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            Log::error('[Instagram] Single post scrape failed - Apify error', [
                'url' => $request->post_url,
                'error' => $e->getMessage(),
                'body' => $errorBody,
            ]);
            return back()->with('error', 'Gagal mengambil data dari Apify: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('[Instagram] Single post scrape failed', [
                'url' => $request->post_url,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal scrape post: ' . $e->getMessage());
        }
    }

    /**
     * Download images for a single post
     */
    protected function downloadImagesForPost(array $imageUrls, string $username, string $shortcode): array
    {
        $savedPaths = [];
        $downloadDir = base_path("instagram-scraper/downloads/{$username}");

        if (!file_exists($downloadDir)) {
            mkdir($downloadDir, 0755, true);
        }

        $client = new \GuzzleHttp\Client([
            'verify' => false,
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);

        foreach ($imageUrls as $index => $url) {
            try {
                $extension = 'jpg';
                $filename = "{$shortcode}_" . ($index + 1) . ".{$extension}";
                $finalPath = "{$downloadDir}/{$filename}";

                $response = $client->get($url);
                if ($response->getStatusCode() === 200) {
                    file_put_contents($finalPath, $response->getBody()->getContents());
                    $savedPaths[] = "/downloads/{$username}/{$filename}";
                }
            } catch (\Exception $e) {
                Log::warning('[Instagram] Failed to download image', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $savedPaths;
    }

    /**
     * Cleanup all stuck processing posts
     */
    public function cleanupStuckPosts()
    {
        try {
            $minutes = 10; // Posts stuck for more than 10 minutes
            
            $updated = DB::table('sc_raw_news_feeds')
                ->whereNotNull('processing_status')
                ->where('is_processed', false)
                ->where('updated_at', '<', now()->subMinutes($minutes))
                ->update([
                    'processing_status' => null,
                    'error_message' => 'Processing timed out. Ready for retry.',
                    'updated_at' => now(),
                ]);

            if ($updated > 0) {
                Log::info('[Instagram] Cleaned up stuck processing posts', ['count' => $updated]);
                return back()->with('success', "{$updated} post yang stuck berhasil di-reset.");
            }

            return back()->with('info', 'Tidak ada post yang stuck.');
        } catch (\Exception $e) {
            Log::error('[Instagram] Failed to cleanup stuck posts', [
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal cleanup: ' . $e->getMessage());
        }
    }
}
