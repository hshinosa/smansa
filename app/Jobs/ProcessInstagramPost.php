<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\RagService;
use App\Services\ContentCreationService;
use App\Services\GroqService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessInstagramPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 180; // Increased to 3 minutes for AI processing
    public $backoff = [30, 60, 120];
    
    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 3;

    protected int $scrapedPostId;
    protected string $title;
    protected string $category;
    protected int $authorId;
    protected bool $useAI;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $scrapedPostId,
        string $title,
        string $category,
        int $authorId,
        bool $useAI = false
    ) {
        $this->scrapedPostId = $scrapedPostId;
        $this->title = $title;
        $this->category = $category;
        $this->authorId = $authorId;
        $this->useAI = $useAI;

        // Use 'default' queue for better compatibility - ensure queue worker is listening
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(RagService $ragService, ContentCreationService $contentCreationService, GroqService $groqService): void
    {
        Log::info('[InstagramJob] ===== JOB EXECUTION STARTED =====', [
            'scraped_id' => $this->scrapedPostId,
            'use_ai' => $this->useAI,
            'queue_connection' => config('queue.default'),
            'job_attempt' => $this->attempts(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Implement Redis-based atomic lock to prevent race conditions (extended timeout for AI processing)
        $lock = \Illuminate\Support\Facades\Cache::lock('process_instagram_post_' . $this->scrapedPostId, 200);

        if (!$lock->get()) {
            Log::warning('[InstagramJob] Job already being processed by another worker - releasing early', ['scraped_id' => $this->scrapedPostId]);
            return;
        }

        try {
            Log::info('[InstagramJob] Locked and starting process', ['scraped_id' => $this->scrapedPostId]);

            // Update processing_status to 'processing' to indicate job is actively being worked on
            DB::table('sc_raw_news_feeds')
                ->where('id', $this->scrapedPostId)
                ->update([
                    'processing_status' => 'processing',
                    'updated_at' => now(),
                ]);

            // Get the scraped post (no lockForUpdate - we're using Cache lock instead)
            $scrapedPost = DB::table('sc_raw_news_feeds')->where('id', $this->scrapedPostId)->first();

            if (!$scrapedPost) {
                Log::warning('[InstagramJob] Scraped post not found in DB', ['id' => $this->scrapedPostId]);
                return;
            }

            if ($scrapedPost->is_processed) {
                Log::info('[InstagramJob] Post already marked as processed in DB', [
                    'id' => $this->scrapedPostId,
                    'processed_at' => $scrapedPost->processed_at
                ]);
                
                // Safety: make sure status is null if it's already processed
                DB::table('sc_raw_news_feeds')
                    ->where('id', $this->scrapedPostId)
                    ->update(['processing_status' => null]);
                    
                return;
            }

            // Parse and fix image paths
            $imagePaths = $scrapedPost->image_paths;
            if (is_string($imagePaths)) {
                $imagePaths = json_decode($imagePaths, true) ?? [];
            }

            $fixedImagePaths = [];
            if (!empty($imagePaths) && is_array($imagePaths)) {
                foreach ($imagePaths as $path) {
                    $cleanPath = ltrim($path, '/\\');
                    // Remove "downloads/" prefix if present
                    $cleanPath = preg_replace('#^downloads/#', '', $cleanPath);
                    // Add instagram-scraper/downloads prefix
                    if (!str_starts_with($cleanPath, 'instagram-scraper/')) {
                        $cleanPath = 'instagram-scraper/downloads/' . $cleanPath;
                    }
                    $fixedImagePaths[] = $cleanPath;
                }
            }

            // Prepare title and content
            $title = $this->title;
            $caption = $scrapedPost->caption ?? '';
            $category = $this->category;

            // Use AI to generate/improve content if enabled
            if ($this->useAI) {
                try {
                    // Build image context for AI generation
                    $imageCount = count($fixedImagePaths);
                    
                    // Try AI Vision for ALL images analysis
                    $imageDescriptions = [];
                    if (!empty($fixedImagePaths)) {
                        Log::info('[InstagramJob] Analyzing all images', ['total_images' => $imageCount]);
                        
                        foreach ($fixedImagePaths as $index => $imagePath) {
                            $fullPath = base_path($imagePath);
                            if (file_exists($fullPath)) {
                                Log::info("[InstagramJob] Analyzing image " . ($index + 1) . "/{$imageCount}", ['path' => $imagePath]);
                                $visionResult = $groqService->analyzeImage($fullPath);
                                if ($visionResult['success']) {
                                    $imageDescriptions[] = "Gambar " . ($index + 1) . ": " . $visionResult['message'];
                                } else {
                                    Log::warning("[InstagramJob] Vision failed for image " . ($index + 1), ['error' => $visionResult['error'] ?? 'unknown']);
                                }
                            }
                        }
                    }

                    // Combine all descriptions or use heuristic fallback
                    if (!empty($imageDescriptions)) {
                        $imageDescription = "Deskripsi visual dari {$imageCount} gambar:\n" . implode("\n", $imageDescriptions);
                        Log::info('[InstagramJob] Combined vision analysis successful', ['total_analyzed' => count($imageDescriptions)]);
                    } else {
                        $imageDescription = $this->generateImageDescription($fixedImagePaths, $caption);
                        Log::info('[InstagramJob] Using heuristic image description');
                    }
                    
                    // Get likes/engagement if available
                    $engagement = null;
                    if (isset($scrapedPost->likes_count)) {
                        $engagement = $scrapedPost->likes_count;
                    } elseif (isset($scrapedPost->engagement)) {
                        $engagement = $scrapedPost->engagement;
                    }
                    
                    // Extract hashtags from caption
                    $hashtags = [];
                    preg_match_all('/#(\w+)/u', $caption, $matches);
                    if (!empty($matches[1])) {
                        $hashtags = array_slice($matches[1], 0, 10); // Limit to 10 hashtags
                    }
                    
                    // Use ContentCreationService for AI-generated content
                    $aiResult = $contentCreationService->generateNewsArticle($caption, [
                        'date' => $scrapedPost->scraped_at ?? null,
                        'image_count' => $imageCount,
                        'image_description' => $imageDescription,
                        'engagement' => $engagement,
                        'hashtags' => $hashtags,
                    ]);
                    
                    if ($aiResult['success'] && !empty($aiResult['article'])) {
                        $article = $aiResult['article'];
                        
                        // Use AI-generated title if available
                        if (!empty($article['title'])) {
                            $title = Str::limit($article['title'], 200);
                        }
                        
                        // Use AI-generated category if available
                        if (!empty($article['category'])) {
                            $categoryMap = [
                                'Berita Sekolah' => 'Berita',
                                'Berita' => 'Berita',
                                'Prestasi' => 'Prestasi',
                                'Kegiatan' => 'Kegiatan',
                                'Pengumuman' => 'Pengumuman',
                                'Akademik' => 'Akademik',
                            ];
                            $category = $categoryMap[$article['category']] ?? $this->category;
                        }
                        
                        // Content is already formatted with proper capitalization by ContentCreationService
                        $content = $article['content'];
                        
                        Log::info('[InstagramJob] AI processing completed via ContentCreationService', [
                            'original_title' => $this->title,
                            'ai_title' => $title,
                            'detected_category' => $category,
                        ]);
                    } else {
                        Log::warning('[InstagramJob] AI processing returned no article, using fallback');
                        // Fallback: use ContentCreationService's formatCaptionAsHtml for proper formatting
                        $content = $this->formatContentWithCapitalization($caption);
                    }
                } catch (\Exception $e) {
                    Log::warning('[InstagramJob] AI processing failed, using fallback', [
                        'error' => $e->getMessage(),
                    ]);
                    // Fallback: use ContentCreationService's formatCaptionAsHtml for proper formatting
                    $content = $this->formatContentWithCapitalization($caption);
                }
            } else {
                // Not using AI - still apply proper formatting with capitalization
                $content = $this->formatContentWithCapitalization($caption);
            }

            // Fallback title if still empty
            if (empty($title)) {
                $captionLines = explode("\n", $caption);
                $title = trim($captionLines[0] ?? '') ?: "Berita dari Instagram @{$scrapedPost->source_username}";
                $title = preg_replace('/#\w+/', '', $title); // Remove hashtags
                $title = Str::limit(trim($title), 200);
            }

            // Create unique slug
            $slug = Str::slug($title);
            if (empty($slug)) {
                $slug = 'instagram-post-' . $this->scrapedPostId;
            }
            
            $existingSlug = Post::where('slug', $slug)->exists();
            if ($existingSlug) {
                $slug = $slug . '-' . time();
            }

            // Create draft post
            $post = Post::create([
                'title' => $title,
                'slug' => $slug,
                'excerpt' => Str::limit(strip_tags($content), 200),
                'content' => $content,
                'category' => $category,
                'status' => 'draft',
                'author_id' => $this->authorId,
                'is_featured' => false,
                'views_count' => 0,
            ]);

            Log::info('[InstagramJob] Post created', [
                'post_id' => $post->id,
                'title' => $post->title,
                'category' => $post->category,
            ]);

            // Handle ALL images attachment
            if (!empty($fixedImagePaths)) {
                Log::info('[InstagramJob] Attaching all images to post', [
                    'total_images' => count($fixedImagePaths),
                    'post_id' => $post->id,
                ]);
                
                $attachedCount = 0;
                foreach ($fixedImagePaths as $index => $imagePath) {
                    $fullPath = base_path($imagePath);
                    
                    if (file_exists($fullPath)) {
                        try {
                            $post->addMedia($fullPath)
                                ->preservingOriginal()
                                ->toMediaCollection('featured'); // Spatie supports multiple in one collection
                            
                            $attachedCount++;
                            Log::info("[InstagramJob] Image " . ($index + 1) . " attached successfully", [
                                'post_id' => $post->id,
                                'image' => $imagePath,
                            ]);
                        } catch (\Exception $e) {
                            Log::warning("[InstagramJob] Failed to attach image " . ($index + 1), [
                                'post_id' => $post->id,
                                'image' => $imagePath,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    } else {
                        Log::warning("[InstagramJob] Image file not found", ['path' => $fullPath]);
                    }
                }
                
                Log::info('[InstagramJob] Image attachment completed', [
                    'post_id' => $post->id,
                    'attached' => $attachedCount,
                    'total' => count($fixedImagePaths),
                ]);
            }

            // Mark scraped post as processed and clear processing status
            $updateData = [
                'is_processed' => true,
                'processing_status' => null,
                'processed_post_id' => $post->id,
                'updated_at' => now(),
            ];

            // Only add processed_at if the column exists to avoid errors
            if (\Illuminate\Support\Facades\Schema::hasColumn('sc_raw_news_feeds', 'processed_at')) {
                $updateData['processed_at'] = now();
            }

            DB::table('sc_raw_news_feeds')
                ->where('id', $this->scrapedPostId)
                ->update($updateData);

            Log::info('[InstagramJob] Processing completed', [
                'scraped_id' => $this->scrapedPostId,
                'post_id' => $post->id,
            ]);

        } catch (\Exception $e) {
            Log::error('[InstagramJob] Processing failed', [
                'scraped_id' => $this->scrapedPostId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clear processing status on exception to prevent stuck state
            // This ensures the UI can retry or show error state
            DB::table('sc_raw_news_feeds')
                ->where('id', $this->scrapedPostId)
                ->update([
                    'processing_status' => null,
                    'error_message' => 'Processing error: ' . Str::limit($e->getMessage(), 200),
                    'updated_at' => now(),
                ]);

            throw $e; // Re-throw to trigger retry
        } finally {
            $lock->release();
        }
    }

    /**
     * Format content with proper capitalization (fallback when AI is not used or fails)
     * 
     * @param string $caption
     * @return string
     */
    protected function formatContentWithCapitalization(string $caption): string
    {
        // Escape HTML entities
        $text = htmlspecialchars($caption, ENT_QUOTES, 'UTF-8');
        
        // Apply capitalization
        $text = $this->capitalizeSentences($text);
        
        // Split by double newlines for paragraphs
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $html = '';
        
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (empty($p)) continue;
            
            // Convert single newlines to <br>
            $p = nl2br($p);
            
            // Linkify URLs
            $p = preg_replace(
                '/(https?:\/\/[^\s]+)/', 
                '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">$1</a>', 
                $p
            );
            
            // Linkify Mentions (@username)
            $p = preg_replace(
                '/@(\w+)/', 
                '<a href="https://instagram.com/$1" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">@$1</a>', 
                $p
            );
            
            $html .= "<p class=\"mb-4 text-gray-700 leading-relaxed\">{$p}</p>";
        }
        
        return $html;
    }

    /**
     * Capitalize first letter of each sentence
     * 
     * @param string $text
     * @return string
     */
    protected function capitalizeSentences(string $text): string
    {
        // First, capitalize the very first character of the text
        if (strlen($text) > 0) {
            $text = mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($text, 1, null, 'UTF-8');
        }
        
        // Capitalize after sentence-ending punctuation followed by space/newline
        $text = preg_replace_callback(
            '/([.!?])\s+([a-záàâäãåæçéèêëíìîïñóòôöõøúùûüýÿ])/ui',
            function ($matches) {
                return $matches[1] . ' ' . mb_strtoupper($matches[2], 'UTF-8');
            },
            $text
        );
        
        // Capitalize after newlines
        $text = preg_replace_callback(
            '/(\n\s*)([a-záàâäãåæçéèêëíìîïñóòôöõøúùûüýÿ])/ui',
            function ($matches) {
                return $matches[1] . mb_strtoupper($matches[2], 'UTF-8');
            },
            $text
        );
        
        // Capitalize after numbered list items (1. 2. 3. etc.)
        $text = preg_replace_callback(
            '/(\d+\.\s*)([a-záàâäãåæçéèêëíìîïñóòôöõøúùûüýÿ])/ui',
            function ($matches) {
                return $matches[1] . mb_strtoupper($matches[2], 'UTF-8');
            },
            $text
        );
        
        // Capitalize after bullet points (- or *)
        $text = preg_replace_callback(
            '/([\-\*]\s+)([a-záàâäãåæçéèêëíìîïñóòôöõøúùûüýÿ])/ui',
            function ($matches) {
                return $matches[1] . mb_strtoupper($matches[2], 'UTF-8');
            },
            $text
        );

        return $text;
    }

    /**
     * Generate a contextual description of images based on paths and caption
     * This helps the AI understand what the images likely contain
     * 
     * @param array $imagePaths
     * @param string $caption
     * @return string
     */
    protected function generateImageDescription(array $imagePaths, string $caption): string
    {
        if (empty($imagePaths)) {
            return '';
        }

        $description = '';
        $imageCount = count($imagePaths);

        // Analyze caption for context clues about what the images might show
        $captionLower = strtolower($caption);
        
        // Keywords for different types of activities
        $activityKeywords = [
            'upacara' => 'upacara bendera',
            'lomba' => 'kegiatan lomba/kompetisi',
            'juara' => 'penyerahan piala/penghargaan',
            'wisuda' => 'wisuda/kelulusan',
            'kelas' => 'kegiatan pembelajaran di kelas',
            'laboratorium' => 'kegiatan praktikum laboratorium',
            'lab' => 'kegiatan praktikum laboratorium',
            'olahraga' => 'kegiatan olahraga',
            'pramuka' => 'kegiatan pramuka',
            'ekstrakulikuler' => 'kegiatan ekstrakurikuler',
            'ekstrakurikuler' => 'kegiatan ekstrakurikuler',
            'pentas' => 'pentas seni',
            'seni' => 'kegiatan seni',
            'musik' => 'kegiatan musik',
            'rapat' => 'rapat/pertemuan',
            'pelatihan' => 'pelatihan/workshop',
            'workshop' => 'pelatihan/workshop',
            'seminar' => 'seminar',
            'webinar' => 'webinar/seminar online',
            'siswa' => 'siswa-siswi sekolah',
            'siswi' => 'siswa-siswi sekolah',
            'guru' => 'guru/pendidik',
            'kepsek' => 'kepala sekolah',
            'kepala sekolah' => 'kepala sekolah',
            'osis' => 'kegiatan OSIS',
            'mpk' => 'kegiatan MPK',
            'ppdb' => 'kegiatan PPDB',
            'pendaftaran' => 'proses pendaftaran',
            'pengumuman' => 'pengumuman resmi',
            'vaksin' => 'kegiatan vaksinasi',
            'donor' => 'kegiatan donor darah',
            'literasi' => 'kegiatan literasi/membaca',
            'perpustakaan' => 'kegiatan di perpustakaan',
            'foto bersama' => 'foto bersama/dokumentasi acara',
            'pembinaan' => 'kegiatan pembinaan',
        ];

        $detectedActivities = [];
        foreach ($activityKeywords as $keyword => $activity) {
            if (strpos($captionLower, $keyword) !== false) {
                $detectedActivities[] = $activity;
            }
        }

        if (!empty($detectedActivities)) {
            $uniqueActivities = array_unique($detectedActivities);
            $description = 'Gambar kemungkinan menampilkan ' . implode(', ', array_slice($uniqueActivities, 0, 3));
        } else {
            $description = 'Dokumentasi foto kegiatan sekolah';
        }

        // Add note about multiple images if applicable
        if ($imageCount > 1) {
            $description .= " ({$imageCount} foto dokumentasi)";
        }

        return $description;
    }

    /**
     * Handle a job failure (called after all retries are exhausted).
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[InstagramJob] Job failed permanently after all retries', [
            'scraped_id' => $this->scrapedPostId,
            'error' => $exception->getMessage(),
        ]);

        // Always release the lock on failure to prevent deadlock
        try {
            $lock = \Illuminate\Support\Facades\Cache::lock('process_instagram_post_' . $this->scrapedPostId);
            $lock->forceRelease();
        } catch (\Exception $e) {
            Log::warning('[InstagramJob] Could not release lock on failure', ['error' => $e->getMessage()]);
        }

        // Mark as failed and clear processing status to stop looping in UI
        DB::table('sc_raw_news_feeds')
            ->where('id', $this->scrapedPostId)
            ->update([
                'processing_status' => null,
                'error_message' => 'Job failed permanently: ' . Str::limit($exception->getMessage(), 200),
                'updated_at' => now(),
            ]);
    }
}
