<?php

namespace Tests\Feature;

use App\Jobs\ProcessInstagramPost;
use App\Models\Post;
use App\Models\Admin;
use App\Services\GroqService;
use App\Services\RagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class InstagramWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup default admin for author_id
        Admin::factory()->create(['id' => 1]);
    }

    #[Test]
    public function it_can_process_instagram_feed_into_news_draft_with_ai()
    {
        // 1. Arrange: Setup Mock Data & Fakes
        Storage::fake('public');
        
        // Create dummy image in fake storage to simulate scraped image
        $dummyImagePath = 'instagram-scraper/downloads/sman1baleendah/test_post_1.jpg';
        Storage::disk('local')->put($dummyImagePath, 'fake image content');
        
        // Mock DB record in sc_raw_news_feeds
        $scrapedId = DB::table('sc_raw_news_feeds')->insertGetId([
            'post_shortcode' => 'TEST123SHORT',
            'source_username' => 'sman1baleendah',
            'caption' => 'Selamat kepada tim basket SMAN 1 Baleendah yang telah menjadi juara 1 di kompetisi tingkat nasional! #prestasi #juara',
            'image_paths' => json_encode(['/downloads/sman1baleendah/test_post_1.jpg']),
            'is_processed' => false,
            'scraped_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mock GroqService
        $mockGroq = Mockery::mock(GroqService::class);
        $mockGroq->shouldReceive('chatCompletion')
            ->andReturn([
                'success' => true,
                'message' => 'Artikel Berita: Keberhasilan Tim Basket SMAN 1 Baleendah Mendapatkan Juara Nasional. Tim basket sekolah baru saja memenangkan kompetisi bergengsi tingkat nasional...',
                'provider' => 'groq'
            ]);
        $mockGroq->shouldReceive('contentCompletion')
            ->andReturn([
                'success' => true,
                'message' => 'Artikel Berita: Keberhasilan Tim Basket SMAN 1 Baleendah Mendapatkan Juara Nasional. Tim basket sekolah baru saja memenangkan kompetisi bergengsi tingkat nasional...',
                'provider' => 'groq'
            ]);
        $this->app->instance(GroqService::class, $mockGroq);

        // Mock RagService (used for title generation in original code)
        $mockRag = Mockery::mock(RagService::class);
        $mockRag->shouldReceive('generateRagResponse')
            ->andReturn([
                'success' => true,
                'message' => 'Juara Nasional Basket SMAN 1 Baleendah',
                'is_rag_enhanced' => true
            ]);
        $this->app->instance(RagService::class, $mockRag);

        // 2. Act: Dispatch the job manually
        // We use dispatchSync to run it immediately in the test
        ProcessInstagramPost::dispatchSync(
            $scrapedId,
            'Manual Title',
            'prestasi',
            1, // author_id
            true // useAI
        );

        // 3. Assert: Verify results
        // Assert news draft created
        $this->assertDatabaseHas('posts', [
            'category' => 'Berita',
            'status' => 'draft',
            'author_id' => 1,
        ]);

        $post = Post::first();
        $this->assertNotNull($post);
        // Depending on useAI, title might be 'Manual Title' or AI generated title
        // In the test Act, we pass 'Manual Title' but useAI is true.
        // ProcessInstagramPost.php:123 overwrites $title if useAI is true.
        $this->assertNotEmpty($post->title);

        // Assert scrap feed marked as processed
        $this->assertDatabaseHas('sc_raw_news_feeds', [
            'id' => $scrapedId,
            'is_processed' => true,
            'processed_post_id' => $post->id
        ]);

        // Assert lock was handled (verification via log or absence of exception is enough here)
        $this->assertFalse(Cache::has('process_instagram_post_' . $scrapedId));
    }

    #[Test]
    public function it_prevents_race_condition_using_redis_lock()
    {
        $scrapedId = DB::table('sc_raw_news_feeds')->insertGetId([
            'post_shortcode' => 'LOCKTEST',
            'source_username' => 'sman1baleendah',
            'caption' => 'Testing Lock',
            'image_paths' => json_encode([]),
            'is_processed' => false,
        ]);

        // Manually acquire lock
        $lockKey = 'process_instagram_post_' . $scrapedId;
        Cache::lock($lockKey, 10)->get();

        // Dispatch job - should return early because lock is active
        ProcessInstagramPost::dispatchSync(
            $scrapedId,
            'Title',
            'berita',
            1,
            false
        );

        // Assert post was NOT created and feed NOT processed
        $this->assertDatabaseMissing('posts', ['title' => 'Title']);
        $this->assertDatabaseHas('sc_raw_news_feeds', [
            'id' => $scrapedId,
            'is_processed' => false
        ]);
    }
}
