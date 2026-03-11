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
        Schema::create('sc_raw_news_feeds', function (Blueprint $table) {
            $table->id();
            $table->string('post_shortcode')->unique();
            $table->string('source_username');
            $table->text('caption')->nullable();
            $table->json('image_paths')->nullable(); // Local paths to downloaded images
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->timestamp('scraped_at')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('source_username');
            $table->index('is_processed');
            $table->index('scraped_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sc_raw_news_feeds');
    }
};
