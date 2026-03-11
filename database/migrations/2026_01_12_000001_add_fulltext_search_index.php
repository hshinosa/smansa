<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add full-text search support for PostgreSQL
     * Creates a GIN index on posts table for efficient text search
     */
    public function up(): void
    {
        // Check if we're using PostgreSQL
        if (DB::connection()->getDriverName() === 'pgsql') {
            // Create a generated column for tsvector (PostgreSQL 12+)
            DB::statement("
                ALTER TABLE posts 
                ADD COLUMN IF NOT EXISTS search_vector tsvector 
                GENERATED ALWAYS AS (
                    setweight(to_tsvector('indonesian', COALESCE(title, '')), 'A') ||
                    setweight(to_tsvector('indonesian', COALESCE(excerpt, '')), 'B') ||
                    setweight(to_tsvector('indonesian', COALESCE(content, '')), 'C')
                ) STORED
            ");

            // Create GIN index on the search vector
            DB::statement("
                CREATE INDEX IF NOT EXISTS posts_search_vector_idx 
                ON posts USING GIN (search_vector)
            ");
        }

        // For other databases (MySQL/MariaDB), use FULLTEXT indexes
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE posts ADD FULLTEXT INDEX posts_fulltext_idx (title, excerpt, content)');
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            // Drop the GIN index
            DB::statement("DROP INDEX IF EXISTS posts_search_vector_idx");
            
            // Drop the generated column
            DB::statement("ALTER TABLE posts DROP COLUMN IF EXISTS search_vector");
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE posts DROP INDEX posts_fulltext_idx');
        }
    }
};
