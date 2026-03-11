<?php

use App\Models\ProgramStudiSetting;
use Illuminate\Database\Migrations\Migration;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Media::query()
            ->where('model_type', ProgramStudiSetting::class)
            ->where('collection_name', 'alumni_spotlight_image')
            ->delete();

        ProgramStudiSetting::query()
            ->where('section_key', 'alumni_spotlight')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible cleanup migration.
    }
};
