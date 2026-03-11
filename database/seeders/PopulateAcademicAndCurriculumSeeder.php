<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicCalendarContent;
use App\Models\CurriculumSetting;
use Illuminate\Support\Facades\File;

class PopulateAcademicAndCurriculumSeeder extends Seeder
{
    public function run()
    {
        $this->seedAcademicCalendar();
        $this->seedCurriculum();
    }

    private function seedAcademicCalendar()
    {
        $fotoGuruPath = base_path('foto-guru');
        $smansaPath = $fotoGuruPath . DIRECTORY_SEPARATOR . 'SMANSA.jpeg';

        AcademicCalendarContent::truncate();

        // Semester Ganjil 2024/2025
        $cal1 = AcademicCalendarContent::create([
            'title' => 'Kalender Akademik Semester Ganjil',
            'semester' => 1,
            'academic_year_start' => 2024,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Semester Genap 2024/2025
        $cal2 = AcademicCalendarContent::create([
            'title' => 'Kalender Akademik Semester Genap',
            'semester' => 2,
            'academic_year_start' => 2024,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Attach Smansa Image
        if (File::exists($smansaPath)) {
            $cal1->clearMediaCollection('calendar_images');
            $cal1->addMedia($smansaPath)->preservingOriginal()->toMediaCollection('calendar_images');
            
            $cal2->clearMediaCollection('calendar_images');
            $cal2->addMedia($smansaPath)->preservingOriginal()->toMediaCollection('calendar_images');
        }

        $this->command->info('Academic Calendar populated.');
    }

    private function seedCurriculum()
    {
        $smansaPath = base_path('smansa-dokumen');
        $infographicDeepLearning = $smansaPath . DIRECTORY_SEPARATOR . 'Kurikulum.jpeg';
        $infographicEducation2045 = $smansaPath . DIRECTORY_SEPARATOR . 'Kurikulum.png';

        $sections = CurriculumSetting::getSectionFields();
        $mediaCollections = CurriculumSetting::getMediaCollections();

        foreach ($sections as $key => $defaultContent) {
            $setting = CurriculumSetting::updateOrCreate(
                ['section_key' => $key],
                ['content' => $defaultContent]
            );

            $path = null;
            $collection = $mediaCollections[$key] ?? null;

            if ($key === 'infographic_deep_learning') {
                $path = $infographicDeepLearning;
            } elseif ($key === 'infographic_education_2045') {
                $path = $infographicEducation2045;
            }

            if ($path && $collection && File::exists($path)) {
                $setting->clearMediaCollection($collection);
                $setting->addMedia($path)->preservingOriginal()->toMediaCollection($collection);
            }
        }
        
        $this->command->info('Curriculum Settings populated.');
    }
}
