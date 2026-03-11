<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;
use App\Models\Gallery;
use Illuminate\Support\Facades\Storage;

class MigrateImagesToMediaLibrary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:migrate 
                            {--model= : Specific model to migrate (post, gallery, all)}
                            {--dry-run : Run without actually migrating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing images from storage to Media Library with WebP conversions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $model = $this->option('model') ?? 'all';
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🚀 Starting image migration to Media Library...');
        $this->newLine();

        if ($model === 'all' || $model === 'post') {
            $this->migratePosts($dryRun);
        }

        if ($model === 'all' || $model === 'gallery') {
            $this->migrateGalleries($dryRun);
        }

        $this->newLine();
        $this->info('✅ Migration completed!');
    }

    /**
     * Migrate Post featured images
     */
    protected function migratePosts(bool $dryRun = false)
    {
        $this->info('📰 Migrating Post featured images...');

        $posts = Post::whereNotNull('featured_image')
            ->whereDoesntHave('media', function ($query) {
                $query->where('collection_name', 'featured');
            })
            ->get();

        if ($posts->isEmpty()) {
            $this->warn('   No posts to migrate');
            return;
        }

        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        $migrated = 0;
        $failed = 0;

        foreach ($posts as $post) {
            try {
                $imagePath = $post->featured_image;
                
                // Try different path combinations
                $possiblePaths = [
                    storage_path('app/public/' . $imagePath),
                    public_path($imagePath),
                    public_path('storage/' . $imagePath),
                ];

                $foundPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $foundPath = $path;
                        break;
                    }
                }

                if (!$foundPath) {
                    $this->newLine();
                    $this->error("   ❌ Image not found for post #{$post->id}: {$imagePath}");
                    $failed++;
                    $bar->advance();
                    continue;
                }

                if (!$dryRun) {
                    $post->addMedia($foundPath)
                        ->preservingOriginal()
                        ->toMediaCollection('featured');
                    
                    $migrated++;
                }

                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("   ❌ Error migrating post #{$post->id}: " . $e->getMessage());
                $failed++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        
        if ($dryRun) {
            $this->info("   Would migrate: {$posts->count()} posts");
        } else {
            $this->info("   ✅ Migrated: {$migrated} posts");
            if ($failed > 0) {
                $this->warn("   ⚠️  Failed: {$failed} posts");
            }
        }
    }

    /**
     * Migrate Gallery images
     */
    protected function migrateGalleries(bool $dryRun = false)
    {
        $this->info('🖼️  Migrating Gallery images...');

        $galleries = Gallery::where('type', 'image')
            ->whereNotNull('url')
            ->where('is_external', false)
            ->whereDoesntHave('media', function ($query) {
                $query->where('collection_name', 'images');
            })
            ->get();

        if ($galleries->isEmpty()) {
            $this->warn('   No galleries to migrate');
            return;
        }

        $bar = $this->output->createProgressBar($galleries->count());
        $bar->start();

        $migrated = 0;
        $failed = 0;

        foreach ($galleries as $gallery) {
            try {
                $imagePath = $gallery->url;

                // Try different path combinations
                $possiblePaths = [
                    storage_path('app/public/' . $imagePath),
                    public_path($imagePath),
                    public_path('storage/' . $imagePath),
                ];

                $foundPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $foundPath = $path;
                        break;
                    }
                }

                if (!$foundPath) {
                    $this->newLine();
                    $this->error("   ❌ Image not found for gallery #{$gallery->id}: {$imagePath}");
                    $failed++;
                    $bar->advance();
                    continue;
                }

                if (!$dryRun) {
                    $gallery->addMedia($foundPath)
                        ->preservingOriginal()
                        ->toMediaCollection('images');
                    
                    $migrated++;
                }

                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("   ❌ Error migrating gallery #{$gallery->id}: " . $e->getMessage());
                $failed++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        
        if ($dryRun) {
            $this->info("   Would migrate: {$galleries->count()} galleries");
        } else {
            $this->info("   ✅ Migrated: {$migrated} galleries");
            if ($failed > 0) {
                $this->warn("   ⚠️  Failed: {$failed} galleries");
            }
        }
    }
}
