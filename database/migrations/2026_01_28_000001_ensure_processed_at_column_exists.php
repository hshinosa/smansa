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
        // Ensure processed_at exists
        if (!Schema::hasColumn('sc_raw_news_feeds', 'processed_at')) {
            Schema::table('sc_raw_news_feeds', function (Blueprint $table) {
                $table->timestamp('processed_at')->nullable()->after('is_processed');
            });
        }

        // Ensure processing_status exists
        if (!Schema::hasColumn('sc_raw_news_feeds', 'processing_status')) {
            Schema::table('sc_raw_news_feeds', function (Blueprint $table) {
                $table->string('processing_status')->nullable()->after('processed_at');
                $table->index('processing_status');
            });
        }

        // Ensure processed_post_id exists
        if (!Schema::hasColumn('sc_raw_news_feeds', 'processed_post_id')) {
            Schema::table('sc_raw_news_feeds', function (Blueprint $table) {
                $table->unsignedBigInteger('processed_post_id')->nullable()->after('processing_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way fix migration, no down action needed to be safe
    }
};
