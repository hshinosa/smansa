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
        Schema::table('sc_raw_news_feeds', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('is_processed');
            $table->string('processing_status')->nullable()->after('processed_at');
            $table->unsignedBigInteger('processed_post_id')->nullable()->after('processing_status');
            
            $table->index('processing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sc_raw_news_feeds', function (Blueprint $table) {
            $table->dropIndex(['processing_status']);
            $table->dropColumn(['processing_status', 'processed_post_id']);
        });
    }
};
