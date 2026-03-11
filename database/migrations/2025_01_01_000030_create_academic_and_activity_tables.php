<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create academic and student activity tables
     */
    public function up(): void
    {
        // Academic Calendar
        Schema::create('academic_calendar_contents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('calendar_image_url')->nullable();
            $table->integer('academic_year_start')->nullable(); // e.g. 2024
            $table->string('semester')->nullable(); // 'Ganjil', 'Genap'
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index(['academic_year_start', 'semester']);
        });

        // Curriculum Settings (Intro, Fase E, Fase F, methods)
        Schema::create('curriculum_settings', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique();
            $table->json('content')->nullable();
            $table->timestamps();
        });

        // Program Studi Settings (MIPA, IPS, Bahasa detail pages) - Final schema
        Schema::create('program_studi_settings', function (Blueprint $table) {
            $table->id();
            $table->string('program_name'); // 'mipa', 'ips', 'bahasa'
            $table->string('section_key'); // 'hero', 'core_subjects', 'facilities', 'career_paths', 'alumni_spotlight'
            $table->json('content')->nullable();
            $table->string('thumbnail_card_url')->nullable(); // For landing page display
            $table->timestamps();
            
            $table->unique(['program_name', 'section_key']);
        });

        // SPMB Settings (Admission/Registration Settings)
        Schema::create('spmb_settings', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique();
            $table->json('content')->nullable();
            $table->timestamps();
        });

        // Extracurriculars - Final schema
        Schema::create('extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // Olahraga, Seni, Akademik
            $table->text('description');
            $table->string('image_url')->nullable();
            $table->string('icon_name')->nullable();
            $table->string('schedule')->nullable(); // e.g. Senin & Rabu, 15:00
            $table->string('coach_name')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('achievements')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('category');
            $table->index(['is_active', 'sort_order']);
        });

        // Posts (News, Announcements, Achievements) - Final schema
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->string('category')->default('Berita'); // Berita, Pengumuman, Prestasi
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('author_id')->nullable();
            $table->integer('views_count')->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index('status');
            $table->index(['status', 'published_at']);
            $table->index('category');
            $table->index('views_count');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('extracurriculars');
        Schema::dropIfExists('spmb_settings');
        Schema::dropIfExists('program_studi_settings');
        Schema::dropIfExists('curriculum_settings');
        Schema::dropIfExists('academic_calendar_contents');
    }
};
