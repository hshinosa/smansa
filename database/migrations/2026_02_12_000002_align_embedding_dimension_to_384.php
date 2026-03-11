<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align pgvector embedding dimension to 384 for intfloat/multilingual-e5-small
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

            // Drop old HNSW index before changing vector type
            DB::statement('DROP INDEX IF EXISTS rag_document_chunks_embedding_idx');

            // If vectors currently 768 with zero-padding, trim to 384 using subvector
            DB::statement('ALTER TABLE rag_document_chunks ALTER COLUMN embedding TYPE vector(384) USING subvector(embedding, 1, 384)::vector(384)');

            // Recreate HNSW index for cosine similarity
            DB::statement('CREATE INDEX rag_document_chunks_embedding_idx ON rag_document_chunks USING hnsw (embedding vector_cosine_ops) WITH (m = 16, ef_construction = 64)');
        } catch (\Exception $e) {
            // Keep migration non-fatal in environments where pgvector is unavailable
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('DROP INDEX IF EXISTS rag_document_chunks_embedding_idx');
            DB::statement('ALTER TABLE rag_document_chunks ALTER COLUMN embedding TYPE vector(768) USING embedding::vector(768)');
            DB::statement('CREATE INDEX rag_document_chunks_embedding_idx ON rag_document_chunks USING hnsw (embedding vector_cosine_ops) WITH (m = 16, ef_construction = 64)');
        } catch (\Exception $e) {
            // no-op
        }
    }
};
