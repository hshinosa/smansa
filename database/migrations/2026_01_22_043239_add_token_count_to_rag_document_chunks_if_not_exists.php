<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if token_count column exists
        if (!Schema::hasColumn('rag_document_chunks', 'token_count')) {
            Schema::table('rag_document_chunks', function (Blueprint $table) {
                $table->integer('token_count')->nullable()->after('chunk_index');
            });
            
            echo "✓ Added token_count column to rag_document_chunks\n";
        } else {
            echo "✓ token_count column already exists in rag_document_chunks\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('rag_document_chunks', 'token_count')) {
            Schema::table('rag_document_chunks', function (Blueprint $table) {
                $table->dropColumn('token_count');
            });
        }
    }
};
