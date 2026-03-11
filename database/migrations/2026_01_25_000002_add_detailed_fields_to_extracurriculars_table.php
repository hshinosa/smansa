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
        Schema::table('extracurriculars', function (Blueprint $table) {
            // Background image for modal header
            $table->string('bg_image')->nullable()->after('image_url');
            
            // Profile/icon image for the activity
            $table->string('profile_image')->nullable()->after('bg_image');
            
            // Detailed activity description
            $table->text('activity_description')->nullable()->after('description');
            
            // JSON field for structured achievements data
            $table->json('achievements_data')->nullable()->after('achievements');
            
            // JSON field for training/meeting information
            $table->json('training_info')->nullable()->after('schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->dropColumn([
                'bg_image',
                'profile_image',
                'activity_description',
                'achievements_data',
                'training_info'
            ]);
        });
    }
};
