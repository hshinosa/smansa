<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RagDocument;
use App\Services\RagService;
use Illuminate\Support\Facades\DB;

class TestPgvectorRag extends Command
{
    protected $signature = 'test:pgvector-rag';
    protected $description = 'Test pgvector RAG functionality';

    public function handle()
    {
        $this->info('=== Testing pgvector RAG Functionality ===');
        $this->newLine();

        // 1. Create test document
        $this->info('1. Creating test RAG document...');
        $document = RagDocument::create([
            'title' => 'Informasi PPDB SMAN 1 Baleendah',
            'content' => 'PPDB (Penerimaan Peserta Didik Baru) SMAN 1 Baleendah dibuka setiap tahun pada bulan Juni-Juli. ' .
                         'Sekolah kami menerima siswa baru melalui jalur zonasi, prestasi, dan afirmasi. ' .
                         'Pendaftaran dilakukan secara online melalui portal PPDB Jawa Barat. ' .
                         'Biaya pendidikan di SMAN 1 Baleendah gratis karena didanai oleh pemerintah. ' .
                         'Untuk informasi lebih lanjut, hubungi panitia PPDB di nomor telepon sekolah.',
            'category' => 'PPDB',
            'source' => 'manual',
            'is_active' => true,
            'is_processed' => false,
        ]);

        $this->line("   ✓ Document created with ID: {$document->id}");
        $this->newLine();

        // 2. Process document
        $this->info('2. Processing document (chunking + embedding)...');
        $ragService = app(RagService::class);

        try {
            $result = $ragService->processDocument($document);
            
            if ($result) {
                $this->line("   ✓ Document processed successfully");
            } else {
                $this->error("   ✗ Document processing failed");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("   ✗ Error: " . $e->getMessage());
            $this->newLine();
            $this->error("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
        $this->newLine();

        // 3. Check chunks
        $this->info('3. Checking stored chunks...');
        $chunks = DB::select(
            "SELECT id, document_id, chunk_index, LEFT(content, 50) as content_preview 
             FROM rag_document_chunks 
             WHERE document_id = ?", 
            [$document->id]
        );

        if (count($chunks) > 0) {
            $this->line("   ✓ Found " . count($chunks) . " chunks:");
            foreach ($chunks as $chunk) {
                $this->line("     - Chunk #{$chunk->chunk_index}: {$chunk->content_preview}...");
            }
        } else {
            $this->error("   ✗ No chunks found");
        }
        $this->newLine();

        // 4. Test vector search
        $this->info('4. Testing vector similarity search...');
        $this->newLine();

        $testQueries = [
            'Kapan PPDB dibuka?',
            'Berapa biaya sekolah?',
            'Jalur apa saja yang tersedia untuk PPDB?',
        ];

        foreach ($testQueries as $query) {
            $this->line("   Query: \"$query\"");
            
            try {
                $results = $ragService->retrieveRelevantChunks($query, 3);
                
                if (count($results) > 0) {
                    $this->line("   ✓ Found " . count($results) . " relevant chunks:");
                    foreach ($results as $idx => $result) {
                        $similarity = number_format($result['similarity'] * 100, 2);
                        $preview = substr($result['content'], 0, 80);
                        $this->line("     " . ($idx + 1) . ". Similarity: {$similarity}% - {$preview}...");
                    }
                } else {
                    $this->warn("   ⚠ No relevant chunks found (may need lower threshold)");
                }
                $this->newLine();
                
            } catch (\Exception $e) {
                $this->error("   ✗ Error: " . $e->getMessage());
                $this->newLine();
            }
        }

        // 5. Cleanup
        $this->info('5. Cleanup test data...');
        if ($this->confirm('Do you want to delete the test document?', true)) {
            $document->delete();
            $this->line("   ✓ Test document deleted");
        } else {
            $this->line("   ℹ Test document kept (ID: {$document->id})");
        }
        $this->newLine();

        $this->info('=== Test completed ===');
        return 0;
    }
}
