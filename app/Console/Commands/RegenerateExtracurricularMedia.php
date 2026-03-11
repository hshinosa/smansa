<?php

namespace App\Console\Commands;

use App\Models\Extracurricular;
use Illuminate\Console\Command;

class RegenerateExtracurricularMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extracurricular:regenerate-media';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate media conversions for extracurricular images only';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Regenerating extracurricular media conversions...');

        $extracurriculars = Extracurricular::all();
        $totalMedia = 0;
        
        // Count total media items
        foreach ($extracurriculars as $extracurricular) {
            $totalMedia += $extracurricular->getMedia('images')->count();
            $totalMedia += $extracurricular->getMedia('bg_images')->count();
            $totalMedia += $extracurricular->getMedia('profile_images')->count();
        }

        if ($totalMedia === 0) {
            $this->warn('No media found for extracurriculars.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalMedia} media items to regenerate.");
        $progressBar = $this->output->createProgressBar($totalMedia);
        $progressBar->start();

        $regenerated = 0;
        $failed = 0;

        foreach ($extracurriculars as $extracurricular) {
            foreach (['images', 'bg_images', 'profile_images'] as $collection) {
                $mediaItems = $extracurricular->getMedia($collection);
                
                foreach ($mediaItems as $mediaItem) {
                    try {
                        // Delete existing conversions
                        $mediaItem->deleteGeneratedConversions();
                        
                        // Regenerate all conversions defined in model
                        $mediaItem->model->registerMediaConversions($mediaItem);
                        
                        $regenerated++;
                    } catch (\Exception $e) {
                        $this->newLine();
                        $this->error("Failed: {$mediaItem->file_name} - {$e->getMessage()}");
                        $failed++;
                    }
                    
                    $progressBar->advance();
                }
            }
        }

        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("✓ Successfully regenerated: {$regenerated}");
        
        if ($failed > 0) {
            $this->warn("✗ Failed: {$failed}");
        }
        
        $this->info('Extracurricular media regeneration completed!');

        return Command::SUCCESS;
    }
}
