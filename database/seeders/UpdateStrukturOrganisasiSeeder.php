<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolProfileSetting;
use Illuminate\Support\Facades\File;

class UpdateStrukturOrganisasiSeeder extends Seeder
{
    public function run()
    {
        $fotoGuruPath = base_path('foto-guru');

        // 1. HERO STRUKTUR ORGANISASI
        $hero = SchoolProfileSetting::updateOrCreate(
            ['section_key' => 'hero_organization'],
            ['content' => json_encode([
                'title' => 'Struktur Organisasi SMAN 1 Baleendah',
                'image_url' => '/images/hero-bg-sman1baleendah.jpeg'
            ])]
        );
        
        // Migrate Hero Image
        $heroBgPath = $fotoGuruPath . DIRECTORY_SEPARATOR . 'SMANSA.jpeg';
        if (File::exists($heroBgPath)) {
            $hero->clearMediaCollection('hero_organization_bg');
            $hero->addMedia($heroBgPath)
                ->preservingOriginal()
                ->toMediaCollection('hero_organization_bg');
        }

        // 2. ORGANIZATION CHART CONTENT
        $org = SchoolProfileSetting::updateOrCreate(
            ['section_key' => 'organization'],
            ['content' => json_encode([
                'title' => 'Bagan Struktur Organisasi',
                'description' => 'Struktur organisasi SMA Negeri 1 Baleendah Tahun Pelajaran 2025/2026',
                'image_url' => '/images/hero-bg-sman1baleendah.jpeg'
            ])]
        );

        // Migrate Chart Image
        if (File::exists($heroBgPath)) {
            $org->clearMediaCollection('organization_chart');
            $org->addMedia($heroBgPath)
                ->preservingOriginal()
                ->toMediaCollection('organization_chart');
        }
    }
}
