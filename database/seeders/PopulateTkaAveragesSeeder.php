<?php

namespace Database\Seeders;

use App\Models\TkaAverage;
use Illuminate\Database\Seeder;

class PopulateTkaAveragesSeeder extends Seeder
{
    public function run(): void
    {
        TkaAverage::truncate();

        $data = [
            // Tryout 2025
            ['academic_year' => '2024/2025', 'exam_type' => 'Tryout 1', 'subject_name' => 'Penalaran Umum', 'average_score' => 645.50],
            ['academic_year' => '2024/2025', 'exam_type' => 'Tryout 1', 'subject_name' => 'Pengetahuan Kuantitatif', 'average_score' => 580.25],
            ['academic_year' => '2024/2025', 'exam_type' => 'Tryout 1', 'subject_name' => 'Literasi Bahasa Indonesia', 'average_score' => 710.00],
            ['academic_year' => '2024/2025', 'exam_type' => 'Tryout 1', 'subject_name' => 'Literasi Bahasa Inggris', 'average_score' => 625.75],
            
            // Tryout 2024
            ['academic_year' => '2023/2024', 'exam_type' => 'Ujian Akhir', 'subject_name' => 'Matematika', 'average_score' => 82.5],
            ['academic_year' => '2023/2024', 'exam_type' => 'Ujian Akhir', 'subject_name' => 'Fisika', 'average_score' => 78.4],
            ['academic_year' => '2023/2024', 'exam_type' => 'Ujian Akhir', 'subject_name' => 'Kimia', 'average_score' => 85.1],
            ['academic_year' => '2023/2024', 'exam_type' => 'Ujian Akhir', 'subject_name' => 'Biologi', 'average_score' => 88.0],
        ];

        foreach ($data as $item) {
            TkaAverage::create($item);
        }

        $this->command->info('TKA Averages populated successfully.');
    }
}
