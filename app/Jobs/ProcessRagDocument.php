<?php

namespace App\Jobs;

use App\Models\RagDocument;
use App\Services\RagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRagDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected RagDocument $document;

    /**
     * Create a new job instance.
     */
    public function __construct(RagDocument $document)
    {
        $this->document = $document;
    }

    /**
     * Execute the job.
     */
    public function handle(RagService $ragService): void
    {
        Log::info('Processing RAG document via queue', ['document_id' => $this->document->id]);
        
        try {
            $success = $ragService->processDocument($this->document);
            
            if ($success) {
                Log::info('RAG document processed successfully', ['document_id' => $this->document->id]);
            } else {
                Log::error('RAG document processing failed in job', ['document_id' => $this->document->id]);
            }
        } catch (\Exception $e) {
            Log::error('Exception during RAG document processing job', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
