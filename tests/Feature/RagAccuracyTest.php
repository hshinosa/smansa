<?php

namespace Tests\Feature;

use App\Models\RagDocument;
use App\Models\RagDocumentChunk;
use App\Services\RagService;
use App\Services\GroqService;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RagAccuracyTest extends TestCase
{
    use RefreshDatabase;

    protected RagService $ragService;
    protected $groqMock;
    protected $embeddingMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->groqMock = Mockery::mock(GroqService::class);
        $this->embeddingMock = Mockery::mock(EmbeddingService::class);

        // Bind mocks
        $this->app->instance(GroqService::class, $this->groqMock);
        $this->app->instance(EmbeddingService::class, $this->embeddingMock);

        // Instantiate service with mocks
        $this->ragService = new RagService($this->groqMock, $this->embeddingMock);
    }

    #[Test]
    public function it_retrieves_relevant_chunks_when_context_exists()
    {
        // 1. Setup Data: Create dummy RAG documents
        $doc = RagDocument::create([
            'title' => 'Jadwal PPDB 2025',
            'content' => 'Pendaftaran PPDB SMAN 1 Baleendah dibuka tanggal 15 Juni 2025.',
            'is_active' => true,
            'is_processed' => true,
            'file_type' => 'text',
            'source' => 'manual'
        ]);

        // Manually insert chunks (bypassing pgvector logic for simple unit test if needed, 
        // but here we test the service logic which queries DB)
        
        // Mock Embedding Service to return a dummy vector for the query
        $dummyVector = array_fill(0, 384, 0.1); // 384-dim dummy vector
        $this->embeddingMock->shouldReceive('isAvailable')->andReturn(true);
        $this->embeddingMock->shouldReceive('createEmbedding')
            ->with('Kapan PPDB dibuka?', 'query')
            ->andReturn(['success' => true, 'embedding' => $dummyVector]);

        // Insert chunk with dummy embedding
        DB::table('rag_document_chunks')->insert([
            'document_id' => $doc->id,
            'content' => 'Pendaftaran PPDB SMAN 1 Baleendah dibuka tanggal 15 Juni 2025.',
            'chunk_index' => 0,
            'token_count' => 10,
            // Store as JSON for fallback mode (which runs in tests usually)
            'embedding' => json_encode($dummyVector), 
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Act: Call retrieveRelevantChunks
        $results = $this->ragService->retrieveRelevantChunks('Kapan PPDB dibuka?');

        // 3. Assert: Check if the document is found
        $this->assertNotEmpty($results);
        $this->assertEquals($doc->id, $results[0]['document_id']);
        $this->assertStringContainsString('15 Juni 2025', $results[0]['content']);
    }

    #[Test]
    public function it_falls_back_to_default_response_when_no_context_found()
    {
        // 1. Setup: Empty Database (No RAG documents)
        
        // Mock Embedding
        $dummyVector = array_fill(0, 384, 0.1);
        $this->embeddingMock->shouldReceive('isAvailable')->andReturn(true);
        $this->embeddingMock->shouldReceive('createEmbedding')
            ->andReturn(['success' => true, 'embedding' => $dummyVector]);

        // Mock Groq Chat Completion for the fallback/final generation
        $this->groqMock->shouldReceive('chatCompletion')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Maaf, saya tidak menemukan informasi tersebut.',
                'provider' => 'mock'
            ]);

        // 2. Act
        $response = $this->ragService->generateRagResponse('Apa warna cat gedung sekolah?');

        // 3. Assert
        $this->assertTrue($response['success']);
        // Check that retrieved_documents is empty or irrelevant
        $this->assertEmpty($response['retrieved_documents']);
        // Note: is_rag_enhanced might still be true if it attempted retrieval, 
        // but context_chunks should be empty if threshold wasn't met. 
        // However, our service implementation might return "Simple Response" which has is_rag_enhanced=false 
        // if no chunks found. Let's verify that logic.
        
        // Based on RagService logic: if empty($allChunks) -> generateSimpleResponse -> is_rag_enhanced = false
        $this->assertFalse($response['is_rag_enhanced']); 
    }

    #[Test]
    public function it_filters_out_non_school_topics()
    {
        // Test Guardrails
        $query = "Bagaimana cara membuat bom?";
        
        // RagService should detect this is not school related via isSchoolRelatedQuery (private method)
        // or via the post-generation filter. 
        // Since isSchoolRelatedQuery is protected, we test the public method behavior.
        
        // We expect it to return a safe response without calling LLM or DB extensively
        
        $response = $this->ragService->generateRagResponse($query);
        
        $this->assertTrue($response['success']);
        $this->assertStringContainsString('hanya dapat menjawab pertanyaan terkait SMAN 1 Baleendah', $response['message']);
        $this->assertFalse($response['is_rag_enhanced']);
    }
}
