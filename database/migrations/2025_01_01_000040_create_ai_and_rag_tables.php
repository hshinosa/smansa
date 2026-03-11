<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create AI and RAG (Retrieval-Augmented Generation) tables
     * Using PostgreSQL pgvector for embedding storage instead of Qdrant
     */
    public function up(): void
    {
        // AI Settings
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('embedding_provider')->default('openai');
            $table->timestamps();
        });

        // RAG Documents (Knowledge Base)
        Schema::create('rag_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->default('text'); // pdf, text, url
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('source')->nullable();
            $table->string('category')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_processed')->default(false);
            // vector_id field removed - not needed with pgvector
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('is_processed');
            $table->index('category');
        });

        // RAG Document Chunks (for vector embeddings with pgvector)
        Schema::create('rag_document_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->text('content');
            $table->integer('chunk_index');
            $table->integer('token_count')->nullable();
            // embedding column will be added via raw SQL after table creation
            $table->timestamps();
            
            $table->foreign('document_id')->references('id')->on('rag_documents')->onDelete('cascade');
            $table->index('document_id');
        });

        // Add embedding column for non-pgsql drivers (like sqlite in tests)
        if (DB::connection()->getDriverName() !== 'pgsql') {
            Schema::table('rag_document_chunks', function (Blueprint $table) {
                $table->text('embedding')->nullable();
            });
        }

        // Add pgvector support for PostgreSQL (if available)
        if (DB::connection()->getDriverName() === 'pgsql') {
            try {
                // Check if pgvector is available
                $result = DB::select("SELECT * FROM pg_available_extensions WHERE name = 'vector'");
                
                if (!empty($result)) {
                    // Enable pgvector extension
                    DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
                    
                    // Add vector column (768 dimensions for Ollama nomic-embed-text:v1.5)
                    // Adjust dimensions if using different model (1536 for OpenAI text-embedding-3-small)
                    DB::statement('ALTER TABLE rag_document_chunks ADD COLUMN embedding vector(768)');
                    
                    // Create HNSW index for fast approximate nearest neighbor search
                    // Using cosine distance which is standard for embeddings
                    DB::statement('CREATE INDEX rag_document_chunks_embedding_idx ON rag_document_chunks USING hnsw (embedding vector_cosine_ops)');
                    
                    echo "✓ pgvector extension installed and configured\n";
                } else {
                    echo "⚠ pgvector extension not available - RAG will work with limited functionality\n";
                    echo "  To install: Follow instructions at https://github.com/pgvector/pgvector\n";
                    
                    // Add regular text column as fallback (will store JSON string)
                    DB::statement('ALTER TABLE rag_document_chunks ADD COLUMN embedding TEXT');
                }
            } catch (\Exception $e) {
                echo "⚠ Could not setup pgvector: " . $e->getMessage() . "\n";
                echo "  RAG will work with limited functionality\n";
                
                // Add regular text column as fallback
                DB::statement('ALTER TABLE rag_document_chunks ADD COLUMN embedding TEXT');
            }
        }

        // Chat Histories
        Schema::create('chat_histories', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('sender'); // 'user', 'bot'
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->boolean('is_rag_enhanced')->default(false);
            $table->json('retrieved_documents')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order
        Schema::dropIfExists('chat_histories');
        
        // Drop pgvector extension if it exists
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP EXTENSION IF EXISTS vector CASCADE');
        }
        
        Schema::dropIfExists('rag_document_chunks');
        Schema::dropIfExists('rag_documents');
        Schema::dropIfExists('ai_settings');
    }
};
