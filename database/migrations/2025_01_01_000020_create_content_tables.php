<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create content management tables: landing_page_settings, programs, galleries
     * All incremental updates have been consolidated into the final schema
     */
    public function up(): void
    {
        // Landing Page Settings (Hero, About, Kepsek Welcome, CTA)
        Schema::create('landing_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique();
            $table->json('content')->nullable();
            $table->timestamps();
        });

        // Programs (Academic, Vocational, Extracurricular Highlights)
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_name')->nullable(); // For backward compatibility
            $table->string('icon_name')->nullable();
            $table->string('color_class')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_featured')->default(true);
            $table->string('category')->default('Akademik'); // 'Akademik', 'Vokasi', 'Unggulan', 'Program Studi'
            $table->string('link')->nullable();
            $table->string('slug')->nullable(); // For URL-friendly routing
            $table->timestamps();

            // Indexes
            $table->index('is_featured');
            $table->index('category');
            $table->index(['is_featured', 'category']);
        });

        // Galleries (Photos and Videos) - Consolidated final schema
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url')->nullable(); // Image URL or Video URL
            $table->string('thumbnail_url')->nullable();
            $table->string('type')->default('photo'); // 'photo' or 'video'
            $table->boolean('is_external')->default(false);
            $table->string('category')->nullable(); // 'Kegiatan', 'Fasilitas', 'Prestasi'
            $table->json('tags')->nullable();
            $table->date('date')->nullable(); // Event/photo date
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('is_featured');
        });

        // School Profile Settings (History, Vision Mission, Organization Structure, Facilities)
        Schema::create('school_profile_settings', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique();
            $table->json('content')->nullable();
            $table->timestamps();
        });

        // Teachers & Staff - Final schema
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nip')->nullable();
            $table->string('position'); // Kepala Sekolah, Wakil Kepala, Guru Mapel, Staff TU
            $table->string('type')->default('guru'); // 'guru', 'staff'
            $table->string('department')->nullable(); // Matematika, Bahasa, Tata Usaha
            $table->string('image_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('type');
            $table->index(['is_active', 'sort_order']);
        });

        // Alumni (Testimonials) - Final schema with video support
        Schema::create('alumnis', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->year('graduation_year');
            $table->text('testimonial')->nullable(); // Made nullable
            $table->string('image_url')->nullable();
            // Video support fields
            $table->string('video_url')->nullable();
            $table->string('video_thumbnail_url')->nullable();
            $table->enum('video_source', ['youtube', 'upload'])->nullable();
            $table->enum('content_type', ['text', 'video'])->default('text');
            // Removed fields: current_position, education, category (simplified)
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('is_published');
            $table->index('graduation_year');
            $table->index(['is_published', 'sort_order']);
        });

        // FAQs
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('category')->nullable(); // General, PPDB, Akademik
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('is_published');
            $table->index(['is_published', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('alumnis');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('school_profile_settings');
        Schema::dropIfExists('galleries');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('landing_page_settings');
    }
};
