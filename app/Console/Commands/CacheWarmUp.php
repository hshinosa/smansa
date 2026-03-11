<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class CacheWarmUp extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warmup {--all : Warm up all caches}';

    /**
     * The console command description.
     */
    protected $description = 'Warm up application caches for better performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting cache warmup...');
        $startTime = microtime(true);

        try {
            // Warm up using CacheService
            CacheService::warmUp();

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->info("✅ Cache warmup completed in {$duration}ms");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Cache warmup failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}