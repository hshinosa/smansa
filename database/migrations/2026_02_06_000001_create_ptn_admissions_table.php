<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create PTN Admissions table for tracking student admissions to universities
     */
    public function up(): void
    {
        // PTN (Perguruan Tinggi Negeri) Master Data
        Schema::create('ptn_universities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // UNPAD, ITB, UPI, etc
            $table->string('short_name')->nullable(); // Short name/abbreviation
            $table->string('color', 7)->default('#1E40AF'); // Hex color for charts
            $table->string('logo_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        // Admission Batches (SNBP 2024, SNBT 2025, etc)
        Schema::create('ptn_admission_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "SNBP 2024", "SNBT 2025"
            $table->enum('type', ['SNBP', 'SNBT', 'Mandiri'])->default('SNBP');
            $table->year('year');
            $table->integer('total_students')->default(0); // Cached count
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'year']);
            $table->index('is_published');
        });

        // Individual Student Admissions
        Schema::create('ptn_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('ptn_admission_batches')->onDelete('cascade');
            $table->foreignId('university_id')->constrained('ptn_universities')->onDelete('cascade');
            $table->string('student_name');
            $table->string('class')->nullable(); // XII-A, XII IPA 1, etc
            $table->string('program_studi'); // Teknik Informatika, Kedokteran, etc
            $table->enum('degree', ['D3', 'D4', 'S1'])->default('S1');
            $table->string('gender', 1)->nullable(); // L/P
            $table->timestamps();

            $table->index('batch_id');
            $table->index('university_id');
            $table->index('class');
        });

        // Summary Statistics (cached aggregations per batch)
        Schema::create('ptn_admission_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('ptn_admission_batches')->onDelete('cascade');
            $table->foreignId('university_id')->constrained('ptn_universities')->onDelete('cascade');
            $table->integer('count')->default(0);
            $table->timestamps();

            $table->unique(['batch_id', 'university_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ptn_admission_summaries');
        Schema::dropIfExists('ptn_admissions');
        Schema::dropIfExists('ptn_admission_batches');
        Schema::dropIfExists('ptn_universities');
    }
};
