<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Gallery;

class FixGalleryUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gallery:fix-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix gallery URLs to use Media Library URLs instead of dummy paths';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $galleries = Gallery::with('media')->get();
        $fixed = 0;
        $skipped = 0;

        foreach ($galleries as $gallery) {
            $media = $gallery->getFirstMedia('images');
            
            if ($media) {
                $mediaUrl = $media->getUrl();
                
                // Check if URL needs updating
                if ($gallery->url !== $mediaUrl) {
                    $this->info("Updating gallery ID {$gallery->id}: {$gallery->title}");
                    $this->info("  Old URL: {$gallery->url}");
                    $this->info("  New URL: {$mediaUrl}");
                    
                    $gallery->update(['url' => $mediaUrl]);
                    $fixed++;
                } else {
                    $skipped++;
                }
            } else {
                $this->warn("Gallery ID {$gallery->id} ({$gallery->title}) has no media");
            }
        }

        $this->info("\nSummary:");
        $this->info("  Fixed: {$fixed}");
        $this->info("  Skipped: {$skipped}");
        $this->info("  Total: {$galleries->count()}");
        
        return Command::SUCCESS;
    }
}