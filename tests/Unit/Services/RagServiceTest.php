<?php

namespace Tests\Unit\Services;

use App\Models\AiSetting;
use App\Models\Faq;
use App\Models\Post;
use App\Models\RagDocument;
use App\Models\RagDocumentChunk;
use App\Models\SiteSetting;
use App\Services\EmbeddingService;
use App\Services\GroqService;
use App\Services\RagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Mockery;

class RagServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $mockGroq;
    protected $mockEmbeddingService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create AI settings
        AiSetting::set('rag_enabled', true);
        AiSetting::set('rag_top_k', 5);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create RagService with mocked dependencies
     */
    protected function createRagServiceWithMocks(array $groqMocks = [], array $embeddingMocks = []): RagService
    {
        $this->mockGroq = Mockery::mock(GroqService::class, $groqMocks);
        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class, $embeddingMocks);

        // Bind mocks to container
        app()->instance(GroqService::class, $this->mockGroq);
        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);

        return new RagService($this->mockGroq, $this->mockEmbeddingService);
    }

    /**
     * Test retrieveRelevantChunks returns empty array when embedding service unavailable
     */
    public function test_retrieve_relevant_chunks_returns_empty_when_embedding_unavailable(): void
    {
        $ragService = $this->createRagServiceWithMocks(
            [], // groq mocks
            ['isAvailable' => false] // embedding mocks
        );

        $result = $ragService->retrieveRelevantChunks('test query');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test retrieveRelevantChunks returns empty array when embedding fails
     */
    public function test_retrieve_relevant_chunks_returns_empty_when_embedding_fails(): void
    {
        $ragService = $this->createRagServiceWithMocks(
            [], // groq mocks
            [
                'isAvailable' => true,
                'createEmbedding' => ['success' => false, 'error' => 'Embedding failed'],
            ]
        );

        // Set up the mock to return the specified value
        $this->mockEmbeddingService
            ->shouldReceive('isAvailable')
            ->once()
            ->andReturn(true);

        $this->mockEmbeddingService
            ->shouldReceive('createEmbedding')
            ->once()
            ->andReturn(['success' => false, 'error' => 'Embedding failed']);

        $result = $ragService->retrieveRelevantChunks('test query');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test retrieveRelevantChunks with fallback (SQLite doesn't have pgvector)
     */
    public function test_retrieve_relevant_chunks_uses_fallback_when_pgvector_unavailable(): void
    {
        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class);
        $this->mockGroq = Mockery::mock(GroqService::class);

        $this->mockEmbeddingService
            ->shouldReceive('isAvailable')
            ->once()
            ->andReturn(true);

        $this->mockEmbeddingService
            ->shouldReceive('createEmbedding')
            ->once()
            ->andReturn([
                'success' => true,
                'embedding' => [0.1, 0.2, 0.3, 0.4, 0.5]
            ]);

        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);
        app()->instance(GroqService::class, $this->mockGroq);

        // Create test document and chunk
        $document = RagDocument::create([
            'title' => 'Test Document',
            'content' => 'Test content for RAG',
            'category' => 'Test',
            'is_active' => true,
            'is_processed' => true,
        ]);

        RagDocumentChunk::create([
            'document_id' => $document->id,
            'content' => 'Test content chunk',
            'embedding' => json_encode([0.1, 0.2, 0.3, 0.4, 0.5]),
            'chunk_index' => 0,
            'token_count' => 10,
        ]);

        $ragService = new RagService($this->mockGroq, $this->mockEmbeddingService);
        $result = $ragService->retrieveRelevantChunks('test query');

        $this->assertIsArray($result);
    }

    /**
     * Test cosine similarity calculation
     */
    public function test_cosine_similarity_calculation(): void
    {
        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class);
        $this->mockGroq = Mockery::mock(GroqService::class);

        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);
        app()->instance(GroqService::class, $this->mockGroq);

        $ragService = new RagService($this->mockGroq, $this->mockEmbeddingService);

        $vec1 = [1, 0, 0];
        $vec2 = [1, 0, 0];
        $vec3 = [0, 1, 0];
        $vec4 = [1, 1, 0];

        // Same vectors should have similarity 1.0
        $similarity1 = $this->invokeMethod($ragService, 'cosineSimilarity', [$vec1, $vec2]);
        $this->assertEquals(1.0, $similarity1, '', 0.01);

        // Orthogonal vectors should have similarity 0.0
        $similarity2 = $this->invokeMethod($ragService, 'cosineSimilarity', [$vec1, $vec3]);
        $this->assertEquals(0.0, $similarity2, '', 0.01);

        // 45-degree angle should have similarity ~0.707
        $similarity3 = $this->invokeMethod($ragService, 'cosineSimilarity', [$vec1, $vec4]);
        $this->assertEqualsWithDelta(0.707, $similarity3, 0.01);

        // Different length vectors should return 0
        $similarity4 = $this->invokeMethod($ragService, 'cosineSimilarity', [[1, 0], [1, 0, 0]]);
        $this->assertEquals(0.0, $similarity4);
    }

    /**
     * Test processDocument creates chunks correctly
     */
    public function test_process_document_creates_chunks(): void
    {
        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class);
        $this->mockGroq = Mockery::mock(GroqService::class);

        $this->mockEmbeddingService
            ->shouldReceive('createEmbedding')
            ->zeroOrMoreTimes()
            ->andReturn([
                'success' => true,
                'embedding' => array_fill(0, 384, 0.1)
            ]);

        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);
        app()->instance(GroqService::class, $this->mockGroq);

        $document = RagDocument::create([
            'title' => 'Test Document',
            'content' => 'This is a test content that should be chunked. ' .
                        str_repeat('More content here. ', 50),
            'category' => 'Test',
            'is_active' => true,
            'is_processed' => false,
        ]);

        $ragService = new RagService($this->mockGroq, $this->mockEmbeddingService);
        $result = $ragService->processDocument($document);

        $this->assertTrue($result);
        $this->assertTrue($document->fresh()->is_processed);
        $this->assertGreaterThan(0, $document->chunks()->count());
    }

    /**
     * Test processDocument handles embedding failure gracefully
     */
    public function test_process_document_handles_embedding_failure(): void
    {
        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class);
        $this->mockGroq = Mockery::mock(GroqService::class);

        $this->mockEmbeddingService
            ->shouldReceive('createEmbedding')
            ->zeroOrMoreTimes()
            ->andReturn([
                'success' => false,
                'error' => 'Embedding service unavailable'
            ]);

        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);
        app()->instance(GroqService::class, $this->mockGroq);

        $document = RagDocument::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'category' => 'Test',
            'is_active' => true,
            'is_processed' => false,
        ]);

        $ragService = new RagService($this->mockGroq, $this->mockEmbeddingService);

        // Should still return true even if some chunks fail
        $result = $ragService->processDocument($document);

        $this->assertTrue($result);
    }

    /**
     * Test isSchoolRelatedQuery allows school-related queries
     */
    public function test_is_school_related_query_allows_school_queries(): void
    {
        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class);
        $this->mockGroq = Mockery::mock(GroqService::class);

        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);
        app()->instance(GroqService::class, $this->mockGroq);

        $ragService = new RagService($this->mockGroq, $this->mockEmbeddingService);

        $schoolQueries = [
            'ppdb sman 1 baleendah',
            'jurusan mipa',
            'ekstrakurikuler',
            'prestasi sekolah',
            'biaya sekolah',
        ];

        foreach ($schoolQueries as $query) {
            $result = $this->invokeMethod($ragService, 'isSchoolRelatedQuery', [$query]);
            $this->assertTrue($result, "Query should be school-related: {$query}");
        }
    }

    /**
     * Test isSchoolRelatedQuery blocks non-school queries
     */
    public function test_is_school_related_query_blocks_non_school_queries(): void
    {
        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class);
        $this->mockGroq = Mockery::mock(GroqService::class);

        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);
        app()->instance(GroqService::class, $this->mockGroq);

        $ragService = new RagService($this->mockGroq, $this->mockEmbeddingService);

        $nonSchoolQueries = [
            'cara memasak rendang',
            'cuaca hari ini',
            'prediksi togel',
            'kursus online programming',
        ];

        foreach ($nonSchoolQueries as $query) {
            $result = $this->invokeMethod($ragService, 'isSchoolRelatedQuery', [$query]);
            $this->assertFalse($result, "Query should NOT be school-related: {$query}");
        }
    }

    /**
     * Test calculateKeywordMatch returns correct similarity scores
     */
    public function test_calculate_keyword_match(): void
    {
        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class);
        $this->mockGroq = Mockery::mock(GroqService::class);

        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);
        app()->instance(GroqService::class, $this->mockGroq);

        $ragService = new RagService($this->mockGroq, $this->mockEmbeddingService);

        $query = 'ppdb sekolah';
        $content1 = 'Informasi PPDB SMAN 1 Baleendah';
        $content2 = 'Makanan di kantin sekolah';
        $content3 = 'Ekstrakurikuler basket';

        $score1 = $this->invokeMethod($ragService, 'calculateKeywordMatch', [$query, $content1]);
        $score2 = $this->invokeMethod($ragService, 'calculateKeywordMatch', [$query, $content2]);
        $score3 = $this->invokeMethod($ragService, 'calculateKeywordMatch', [$query, $content3]);

        // content1 has both 'ppdb' and 'sekolah', should have higher score
        $this->assertGreaterThan($score3, $score1);
    }

    /**
     * Test generateRagResponse when RAG is disabled
     */
    public function test_generate_rag_response_when_disabled(): void
    {
        AiSetting::set('rag_enabled', false);

        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class);
        $this->mockGroq = Mockery::mock(GroqService::class);

        $this->mockGroq
            ->shouldReceive('chatCompletion')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Simple response',
                'provider' => 'groq'
            ]);

        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);
        app()->instance(GroqService::class, $this->mockGroq);

        $ragService = new RagService($this->mockGroq, $this->mockEmbeddingService);
        $result = $ragService->generateRagResponse('test query');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['is_rag_enhanced']);
    }

    /**
     * Test generateRagResponse blocks non-school queries
     */
    public function test_generate_rag_response_blocks_non_school_queries(): void
    {
        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class);
        $this->mockGroq = Mockery::mock(GroqService::class);

        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);
        app()->instance(GroqService::class, $this->mockGroq);

        $ragService = new RagService($this->mockGroq, $this->mockEmbeddingService);
        $result = $ragService->generateRagResponse('cara memasak rendang');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['is_rag_enhanced']);
        $this->assertStringContainsString('hanya dapat menjawab pertanyaan terkait SMAN 1 Baleendah', $result['message']);
    }

    /**
     * Test estimateTokenCount
     */
    public function test_estimate_token_count(): void
    {
        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class);
        $this->mockGroq = Mockery::mock(GroqService::class);

        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);
        app()->instance(GroqService::class, $this->mockGroq);

        $ragService = new RagService($this->mockGroq, $this->mockEmbeddingService);

        $shortText = 'Hello';
        $mediumText = str_repeat('word ', 100);
        $longText = str_repeat('word ', 500);

        $count1 = $this->invokeMethod($ragService, 'estimateTokenCount', [$shortText]);
        $count2 = $this->invokeMethod($ragService, 'estimateTokenCount', [$mediumText]);
        $count3 = $this->invokeMethod($ragService, 'estimateTokenCount', [$longText]);

        $this->assertGreaterThan(0, $count1);
        $this->assertGreaterThan($count1, $count2);
        $this->assertGreaterThan($count2, $count3);
    }

    /**
     * Test splitTextIntoChunks
     */
    public function test_split_text_into_chunks(): void
    {
        $this->mockEmbeddingService = Mockery::mock(EmbeddingService::class);
        $this->mockGroq = Mockery::mock(GroqService::class);

        app()->instance(EmbeddingService::class, $this->mockEmbeddingService);
        app()->instance(GroqService::class, $this->mockGroq);

        $ragService = new RagService($this->mockGroq, $this->mockEmbeddingService);

        $longText = str_repeat('This is a sentence. ', 200);

        $chunks = $this->invokeMethod($ragService, 'splitTextIntoChunks', [$longText]);

        $this->assertIsArray($chunks);
        $this->assertGreaterThan(1, count($chunks));

        // Each chunk should not be too large
        foreach ($chunks as $chunk) {
            $tokenCount = $this->invokeMethod($ragService, 'estimateTokenCount', [$chunk]);
            $this->assertLessThanOrEqual(600, $tokenCount); // 512 + overlap
        }
    }

    /**
     * Helper to invoke protected/private methods
     */
    protected function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
