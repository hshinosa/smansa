<?php

namespace App\Console\Commands;

use App\Jobs\TestQueueJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueStressTest extends Command
{
    protected $signature = 'queue:stress-test {count=10} {--fail=0}';
    protected $description = 'Run a stress test on the queue system';

    public function handle()
    {
        $count = (int) $this->argument('count');
        $failCount = (int) $this->option('fail');

        $this->info("Dispatching {$count} jobs to the queue...");

        $start = microtime(true);

        for ($i = 1; $i <= $count; $i++) {
            $shouldFail = ($i <= $failCount);
            TestQueueJob::dispatch($i, $shouldFail);
        }

        $end = microtime(true);
        $duration = $end - $start;

        $this->success("Successfully dispatched {$count} jobs in " . number_format($duration, 4) . " seconds.");
        
        $jobCount = DB::table('jobs')->count();
        $this->info("Current jobs in 'jobs' table: {$jobCount}");
        
        $failedJobCount = DB::table('failed_jobs')->count();
        $this->info("Current jobs in 'failed_jobs' table: {$failedJobCount}");
        
        $this->info("Run 'php artisan queue:work' to process these jobs.");
    }
}
