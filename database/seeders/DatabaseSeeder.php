<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]
        );
        
        $this->call([
            AdminSeeder::class, // Admin Account
            
            // Content Population Seeders
            PopulateAcademicAndCurriculumSeeder::class, // Kurikulum & Kalender
            PopulateExtracurricularsSeeder::class, // Ekskul (Rich JSON achievements)
            PopulateProgramsTableSeeder::class, // Programs List (Landing Page)
            PopulateProgramStudiSeeder::class, // MIPA, IPS, Bahasa (Detail)
            PopulatePostsSeeder::class, // Berita (Rich content)
            PopulateAlumniSeeder::class, // Alumni
            PopulateSpmbSeeder::class, // SPMB (Rich content)
            PopulateFaqSeeder::class, // FAQ Kontak
            PopulateSeragamSeeder::class, // Seragam Sekolah
            
            // Settings & Layout Fixes
            UpdateLandingPageContentSeeder::class, // Landing Page Settings
            UpdateSchoolProfileContentSeeder::class, // Profil Sekolah (History, Facilities)
            UpdateVisiMisiContentSeeder::class, // Visi Misi
            UpdateStrukturOrganisasiSeeder::class, // Struktur Organisasi
            FixContactHeroSeeder::class, // Contact Hero Fix
            PopulateGalleriesSeeder::class, // Gallery Images
            PopulateAiSettingsSeeder::class, // AI Settings
            PtnUniversitySeeder::class, // PTN List
            PopulatePtnAdmissionsSeeder::class, // PTN Admissions Data
            PopulateTkaAveragesSeeder::class, // TKA Results Data

            // Final Content Import
            ImportTeachersFromFolderSeeder::class, // Guru & Staff (Moved to last as requested)
        ]);
    }
}
