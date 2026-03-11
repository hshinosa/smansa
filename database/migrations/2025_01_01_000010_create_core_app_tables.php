<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create core application tables: admins, activity_logs, site_settings, contact_messages
     */
    public function up(): void
    {
        // Admins table for admin authentication
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Activity logs for system audit trail
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->text('details')->nullable();
            $table->string('causer_type')->nullable(); // 'admin', 'user', 'system'
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('causer_type');
            $table->index('causer_id');
            $table->index('created_at');
        });

        // Site settings (General, Hero Teachers, Hero Program, Social Media, Footer)
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique(); // 'general', 'hero_teachers', 'hero_program', 'social_media', 'footer'
            $table->json('content')->nullable();
            $table->timestamps();
        });

        // Contact messages from website contact form
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('is_read');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('admins');
    }
};
