<?php

namespace Database\Seeders;

use App\Models\Extracurricular;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PopulateExtracurricularImagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Populating extracurricular images...');

        $sourceFolder = base_path('data_smansa/SMAN 1 BALEENDAH - 2026/EKSKUL & ORGANISASI');

        if (! File::isDirectory($sourceFolder)) {
            $this->command->error("Source folder not found: {$sourceFolder}");

            return;
        }

        $images = File::glob($sourceFolder.'/*.jpg');
        $totalImages = count($images);

        if ($totalImages === 0) {
            $this->command->error('No images found in source folder');

            return;
        }

        $this->command->info("Found {$totalImages} images");

        $extracurriculars = Extracurricular::orderBy('type')->orderBy('name')->get();

        if ($extracurriculars->isEmpty()) {
            $this->command->error('No extracurriculars found in database');

            return;
        }

        $this->command->info("Found {$extracurriculars->count()} extracurriculars/organizations");

        $imageIndex = 0;
        foreach ($extracurriculars as $extracurricular) {
            if ($imageIndex >= $totalImages) {
                $imageIndex = 0;
            }

            $sourceImage = $images[$imageIndex];
            $imageName = basename($sourceImage);
            $destPath = 'extracurriculars/'.$imageName;

            Storage::disk('public')->put($destPath, File::get($sourceImage));

            $fullPath = Storage::disk('public')->path($destPath);

            $extracurricular->clearMediaCollection('images');
            $extracurricular->addMedia($fullPath)
                ->toMediaCollection('images');

            $extracurricular->update([
                'image_url' => asset('storage/'.$destPath),
            ]);

            $this->command->info("Assigned {$imageName} to {$extracurricular->name} ({$extracurricular->type})");

            $imageIndex++;
        }

        $this->command->info('Extracurricular images populated successfully!');
    }
}
