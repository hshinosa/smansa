<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforce vector dimension for Ollama and optimize HNSW index.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            try {
                // Ensure pgvector extension exists
                DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

                // 1. Enforce dimension 768 (Ollama nomic-embed-text)
                DB::statement('ALTER TABLE rag_document_chunks ALTER COLUMN embedding TYPE vector(768)');

                // 2. Drop existing index if it exists to ensure optimization parameters are applied
                DB::statement('DROP INDEX IF EXISTS rag_document_chunks_embedding_idx');

                // 3. Create optimized HNSW index
                DB::statement("
                    CREATE INDEX rag_document_chunks_embedding_idx 
                    ON rag_document_chunks 
                    USING hnsw (embedding vector_cosine_ops)
                    WITH (m = 16, ef_construction = 64)
                ");

                echo "✓ Vector dimension locked to 768 and HNSW index optimized\n";
            } catch (\Exception $e) {
                echo "⚠ Could not optimize HNSW: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for dimension locking as it's a fixed requirement
    }
};
