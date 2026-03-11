<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $id;
    public $shouldFail;

    public function __construct($id, $shouldFail = false)
    {
        $this->id = $id;
        $this->shouldFail = $shouldFail;
    }

    public function handle()
    {
        Log::info("Processing TestQueueJob ID: {$this->id}");

        if ($this->shouldFail) {
            Log::error("TestQueueJob ID: {$this->id} is failing as requested.");
            throw new \Exception("Simulated job failure for ID: {$this->id}");
        }

        // Simulate some work
        usleep(100000); // 100ms

        Log::info("Successfully processed TestQueueJob ID: {$this->id}");
    }
}
