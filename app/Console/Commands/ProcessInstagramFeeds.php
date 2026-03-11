<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ContentCreationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessInstagramFeeds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instagram:process 
                            {--limit=5 : Maximum number of feeds to process per run}
                            {--dry-run : Simulate processing without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scraped Instagram feeds with AI to create news articles';

    protected ContentCreationService $contentCreation;

    /**
     * Create a new command instance.
     */
    public function __construct(ContentCreationService $contentCreation)
    {
        parent::__construct();
        $this->contentCreation = $contentCreation;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🤖 Processing Instagram Feeds with AI...');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No data will be saved');
            $this->newLine();
        }

        // Get pending feeds
        $pendingFeeds = $this->getPendingFeeds($limit);

        if ($pendingFeeds->isEmpty()) {
            $this->info('✅ No pending feeds to process');
            return Command::SUCCESS;
        }

        $this->info("📋 Found {$pendingFeeds->count()} pending feed(s)");
        $this->newLine();

        $successCount = 0;
        $failCount = 0;

        foreach ($pendingFeeds as $feed) {
            $this->info("Processing: {$feed->post_shortcode} from @{$feed->source_username}");
            
            try {
                $result = $this->processFeed($feed, $dryRun);
                
                if ($result['success']) {
                    $this->line("   ✅ Article created: {$result['title']}");
                    $successCount++;
                } else {
                    $this->error("   ❌ Failed: {$result['error']}");
                    $failCount++;
                }
                
                $this->newLine();
                
            } catch (\Exception $e) {
                $this->error("   ❌ Exception: {$e->getMessage()}");
                $this->markFeedError($feed->id, $e->getMessage());
                $failCount++;
                $this->newLine();
            }
        }

        // Summary
        $this->info("📊 Processing Summary:");
        $this->line("   ✅ Success: {$successCount}");
        $this->line("   ❌ Failed: {$failCount}");
        $this->newLine();

        return $successCount > 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Get pending feeds from database
     */
    protected function getPendingFeeds(int $limit)
    {
        return DB::table('sc_raw_news_feeds')
            ->where('is_processed', false)
            ->whereNull('error_message') // Skip feeds with previous errors
            ->orderBy('scraped_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Process a single feed with AI
     */
    protected function processFeed($feed, bool $dryRun): array
    {
        // Prepare metadata
        $metadata = [
            'hashtags' => $this->extractHashtags($feed->caption),
            'date' => $feed->scraped_at,
            'source' => "@{$feed->source_username}",
            'instagram_url' => "https://www.instagram.com/p/{$feed->post_shortcode}/",
        ];

        // Generate article using AI
        $this->line("   🤖 Generating article with AI...");
        
        $result = $this->contentCreation->generateNewsArticle(
            $feed->caption ?? 'No caption provided',
            $metadata,
            ['max_tokens' => 3000]
        );

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'AI generation failed',
            ];
        }

        $article = $result['article'];
        
        if ($dryRun) {
            $this->line("   📝 DRY RUN - Article preview:");
            $this->line("      Title: {$article['title']}");
            $this->line("      Excerpt: " . Str::limit($article['excerpt'], 80));
            $this->line("      Provider: {$result['provider']}");
            
            return [
                'success' => true,
                'title' => $article['title'],
            ];
        }

        // Create draft post for admin review
        $post = Post::create([
            'title' => $article['title'],
            'slug' => Str::slug($article['title']) . '-' . time(), // Add timestamp to prevent slug collision
            'content' => $article['content'],
            'excerpt' => $article['excerpt'],
            'category' => $article['category'] ?? 'Berita',
            'status' => 'draft', // Draft for admin review
            'featured_image' => null, // Will be set from first image if available
            'meta_description' => $article['meta_description'] ?? '',
            'meta_keywords' => isset($article['keywords']) ? implode(', ', $article['keywords']) : '',
            'author_id' => 1, // Default admin user
            'view_count' => 0,
            'is_featured' => false,
            'published_at' => null, // Null until admin publishes
        ]);

        // Handle multiple images from Instagram post
        if (!empty($feed->image_paths)) {
            $imagePaths = json_decode($feed->image_paths, true);
            if (is_array($imagePaths)) {
                $this->line("      📷 Processing " . count($imagePaths) . " image(s)...");
                $this->attachImagesToPost($post, $imagePaths, $feed->post_shortcode);
            }
        }

        // Mark feed as processed
        DB::table('sc_raw_news_feeds')
            ->where('id', $feed->id)
            ->update([
                'is_processed' => true,
                'processed_at' => now(),
            ]);

        Log::info('[ProcessInstagramFeeds] Article created from Instagram', [
            'feed_id' => $feed->id,
            'post_id' => $post->id,
            'shortcode' => $feed->post_shortcode,
            'title' => $post->title,
            'ai_provider' => $result['provider'],
        ]);

        return [
            'success' => true,
            'title' => $post->title,
            'post_id' => $post->id,
        ];
    }

    /**
     * Extract hashtags from caption
     */
    protected function extractHashtags(string $caption = null): array
    {
        if (!$caption) {
            return [];
        }

        preg_match_all('/#(\w+)/', $caption, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Attach images from Instagram to post as media collection
     */
    protected function attachImagesToPost(Post $post, array $imagePaths, string $shortcode): void
    {
        $baseDownloadPath = base_path('instagram-scraper/downloads');
        
        foreach ($imagePaths as $index => $relativePath) {
            try {
                // Remove leading /downloads/ or downloads/ to get clean relative path
                $cleanPath = preg_replace('#^/?downloads/#', '', $relativePath);
                $fullPath = $baseDownloadPath . '/' . $cleanPath;
                
                // Check if file exists
                if (!file_exists($fullPath)) {
                    $this->warn("         ⚠️  Image not found: {$fullPath}");
                    continue;
                }
                
                // Add to media collection
                // Use 'gallery' collection for multiple images, 'featured' for single image
                $collection = count($imagePaths) > 1 ? 'gallery' : 'featured';
                
                $media = $post->addMedia($fullPath)
                    ->preservingOriginal() // Keep original in downloads folder
                    ->withCustomProperties([
                        'instagram_shortcode' => $shortcode,
                        'order' => $index,
                        'source' => 'instagram'
                    ])
                    ->toMediaCollection($collection);
                
                // Set first image as featured_image URL
                if ($index === 0) {
                    $post->update(['featured_image' => $media->getUrl()]);
                }
                
                $this->line("         ✅ Image " . ($index + 1) . " attached");
                
            } catch (\Exception $e) {
                $this->error("         ❌ Failed to attach image {$index}: {$e->getMessage()}");
                Log::warning('[ProcessInstagramFeeds] Image attachment failed', [
                    'post_id' => $post->id,
                    'image_path' => $relativePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Mark feed with error message
     */
    protected function markFeedError(int $feedId, string $error): void
    {
        try {
            DB::table('sc_raw_news_feeds')
                ->where('id', $feedId)
                ->update([
                    'error_message' => Str::limit($error, 500),
                ]);
        } catch (\Exception $e) {
            Log::error('[ProcessInstagramFeeds] Failed to mark error', [
                'feed_id' => $feedId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
