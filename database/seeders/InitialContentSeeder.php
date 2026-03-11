<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * InitialContentSeeder
 * 
 * Consolidates essential school identity and basic settings
 * to make environment setup faster and cleaner.
 */
class InitialContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            UpdateSchoolProfileContentSeeder::class, // Basic history & info
            UpdateVisiMisiContentSeeder::class,
            PopulateAiSettingsSeeder::class,
            PopulateAcademicAndCurriculumSeeder::class,
        ]);
    }
}
