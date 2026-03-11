<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupStuckProcessingPosts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'instagram:cleanup-stuck 
                            {--minutes=10 : Minutes after which a processing status is considered stuck}
                            {--dry-run : Only show what would be cleaned up without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up Instagram posts that are stuck in processing status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $dryRun = $this->option('dry-run');

        $this->info("Looking for posts stuck in processing for more than {$minutes} minutes...");

        try {
            // Find posts that have been stuck in processing status for too long
            $stuckPosts = DB::table('sc_raw_news_feeds')
                ->whereNotNull('processing_status')
                ->where('is_processed', false)
                ->where('updated_at', '<', now()->subMinutes($minutes))
                ->get();

            if ($stuckPosts->isEmpty()) {
                $this->info('✅ No stuck posts found.');
                return Command::SUCCESS;
            }

            $this->warn("Found {$stuckPosts->count()} stuck post(s):");

            foreach ($stuckPosts as $post) {
                $this->line("  - ID: {$post->id}, Status: {$post->processing_status}, Last updated: {$post->updated_at}");
            }

            if ($dryRun) {
                $this->info('Dry run mode - no changes made.');
                return Command::SUCCESS;
            }

            // Clear the processing status for stuck posts
            $updated = DB::table('sc_raw_news_feeds')
                ->whereNotNull('processing_status')
                ->where('is_processed', false)
                ->where('updated_at', '<', now()->subMinutes($minutes))
                ->update([
                    'processing_status' => null,
                    'error_message' => 'Processing timed out after ' . $minutes . ' minutes. Please retry.',
                    'updated_at' => now(),
                ]);

            Log::info('[Instagram] Cleaned up stuck processing posts', [
                'count' => $updated,
                'threshold_minutes' => $minutes,
            ]);

            $this->info("✅ Cleaned up {$updated} stuck post(s). They can now be retried.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to cleanup stuck posts: ' . $e->getMessage());
            Log::error('[Instagram] Failed to cleanup stuck posts', [
                'error' => $e->getMessage(),
            ]);
            return Command::FAILURE;
        }
    }
}
