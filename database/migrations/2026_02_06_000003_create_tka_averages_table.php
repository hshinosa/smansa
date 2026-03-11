<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tka_averages', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 9); // e.g., "2024-2025" or just "2024"
            $table->string('exam_type'); // e.g., "Try Out 1", "Try Out 2", "UTBK"
            $table->string('subject_name'); // e.g., "Matematika Saintek", "Fisika"
            $table->double('average_score', 8, 2);
            $table->timestamps();

            // Unique constraint to prevent duplicate entries for same subject in same exam
            $table->unique(['academic_year', 'exam_type', 'subject_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tka_averages');
    }
};
