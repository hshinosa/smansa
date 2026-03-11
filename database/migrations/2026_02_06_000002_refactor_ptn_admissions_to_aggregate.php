<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refactor PTN Admissions to aggregate counts instead of individual students
     */
    public function up(): void
    {
        // Drop existing tables that depend on admissions
        Schema::dropIfExists('ptn_admission_summaries');
        Schema::dropIfExists('ptn_admissions');

        // Re-create ptn_admissions table with aggregate structure
        Schema::create('ptn_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('ptn_admission_batches')->onDelete('cascade');
            $table->foreignId('university_id')->constrained('ptn_universities')->onDelete('cascade');
            $table->string('program_studi'); // Jurusan
            $table->integer('count')->default(0); // Jumlah siswa diterima
            $table->timestamps();

            // Unique constraint: one record per Batch-PTN-Prodi combination
            $table->unique(['batch_id', 'university_id', 'program_studi'], 'ptn_admissions_unique_entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a destructive migration, tough to reverse exactly to previous state without backup
        Schema::dropIfExists('ptn_admissions');
        
        // Re-create original structure (simplified for rollback)
        Schema::create('ptn_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id');
            $table->foreignId('university_id');
            $table->string('student_name');
            $table->string('class')->nullable();
            $table->string('program_studi');
            $table->enum('degree', ['D3', 'D4', 'S1'])->default('S1');
            $table->string('gender', 1)->nullable();
            $table->timestamps();
        });
    }
};
