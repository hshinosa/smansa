<?php

namespace App\Console\Commands;

use App\Models\Alumni;
use Illuminate\Console\Command;

class FixAlumniVideoUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alumni:fix-video-urls {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix alumni video URLs to use proper media library paths';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in dry-run mode - no changes will be made');
        }

        $this->info('Scanning alumni with uploaded videos...');
        
        $alumnis = Alumni::where('content_type', 'video')
            ->where('video_source', 'upload')
            ->with('media')
            ->get();

        $this->info("Found {$alumnis->count()} alumni with uploaded videos");

        $updated = 0;
        $skipped = 0;
        $noMedia = 0;

        foreach ($alumnis as $alumni) {
            $videoMedia = $alumni->getFirstMedia('videos');
            
            if (!$videoMedia) {
                $this->warn("  - {$alumni->name}: No video media found, skipping");
                $noMedia++;
                continue;
            }

            $correctUrl = $videoMedia->getUrl();
            $currentUrl = $alumni->video_url;

            // Check if URL needs updating
            if ($currentUrl !== $correctUrl) {
                $this->line("  - {$alumni->name}:");
                $this->line("    Current: {$currentUrl}");
                $this->line("    Correct: {$correctUrl}");

                if (!$dryRun) {
                    $alumni->update(['video_url' => $correctUrl]);
                    $this->info("    ✓ Updated");
                } else {
                    $this->comment("    Would update");
                }
                $updated++;
            } else {
                $this->line("  - {$alumni->name}: Already correct");
                $skipped++;
            }

            // Also fix video thumbnail if exists
            $thumbnailMedia = $alumni->getFirstMedia('video_thumbnails');
            if ($thumbnailMedia) {
                $correctThumbnailUrl = $thumbnailMedia->getUrl();
                $currentThumbnailUrl = $alumni->video_thumbnail_url;

                if ($currentThumbnailUrl !== $correctThumbnailUrl) {
                    $this->line("    Thumbnail current: {$currentThumbnailUrl}");
                    $this->line("    Thumbnail correct: {$correctThumbnailUrl}");

                    if (!$dryRun) {
                        $alumni->update(['video_thumbnail_url' => $correctThumbnailUrl]);
                        $this->info("    ✓ Thumbnail updated");
                    } else {
                        $this->comment("    Would update thumbnail");
                    }
                }
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("  Updated: {$updated}");
        $this->info("  Already correct: {$skipped}");
        $this->info("  No media found: {$noMedia}");

        if ($dryRun && $updated > 0) {
            $this->newLine();
            $this->comment("Run without --dry-run to apply changes");
        }

        return Command::SUCCESS;
    }
}