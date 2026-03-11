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
        // Settings table for storing configuration
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->timestamps();
        });

        // Scraper logs table for tracking scraper runs
        Schema::create('sc_scraper_logs', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('status'); // running, completed, failed
            $table->integer('posts_found')->nullable();
            $table->integer('posts_saved')->nullable();
            $table->text('message')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sc_scraper_logs');
        Schema::dropIfExists('settings');
    }
};
