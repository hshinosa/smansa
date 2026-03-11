<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolProfileSetting;
use Illuminate\Support\Facades\File;

class UpdateVisiMisiContentSeeder extends Seeder
{
    public function run()
    {
        // 1. HERO VISION MISSION
        $hero = SchoolProfileSetting::updateOrCreate(
            ['section_key' => 'hero_vision_mission'],
            ['content' => json_encode([
                'title' => 'Visi & Misi SMAN 1 Baleendah',
                'image_url' => '/images/hero-bg-sman1baleendah.jpeg'
            ])]
        );
        
        // Migrate Hero Image
        $heroPath = base_path('foto-guru' . DIRECTORY_SEPARATOR . 'SMANSA.jpeg');
        if (File::exists($heroPath)) {
            $hero->clearMediaCollection('hero_vision_mission_bg');
            $hero->addMedia($heroPath)
                ->preservingOriginal()
                ->toMediaCollection('hero_vision_mission_bg');
        } else {
            $fallbackPath = public_path('images/hero-bg-sman1baleendah.jpeg');
            if (File::exists($fallbackPath)) {
                $hero->clearMediaCollection('hero_vision_mission_bg');
                $hero->addMedia($fallbackPath)
                    ->preservingOriginal()
                    ->toMediaCollection('hero_vision_mission_bg');
            }
        }

        // 2. VISION MISSION CONTENT
        SchoolProfileSetting::updateOrCreate(
            ['section_key' => 'vision_mission'],
            ['content' => json_encode([
                'vision' => 'Terwujudnya Sekolah yang Unggul dalam Prestasi, Berkarakter, Berbudaya Lingkungan, dan Berwawasan Global berlandaskan Iman dan Taqwa.',
                'mission' => [
                    'Meningkatkan keimanan dan ketaqwaan terhadap Tuhan Yang Maha Esa.',
                    'Mengembangkan kurikulum yang adaptif dan proaktif terhadap perkembangan ilmu pengetahuan dan teknologi.',
                    'Mewujudkan proses pembelajaran yang efektif, inovatif, dan menyenangkan.',
                    'Meningkatkan prestasi akademik dan non-akademik peserta didik.',
                    'Menumbuhkan budaya literasi dan karakter profil pelajar Pancasila.',
                    'Mewujudkan lingkungan sekolah yang bersih, hijau, sehat, dan asri.',
                    'Mengembangkan kemitraan dengan perguruan tinggi dan lembaga terkait.'
                ],
                'goals' => [
                    'Menghasilkan lulusan yang diterima di Perguruan Tinggi Negeri (PTN) minimal 80%.',
                    'Meraih juara dalam kompetisi OSN tingkat Provinsi dan Nasional.',
                    'Terwujudnya sekolah Adiwiyata Mandiri.',
                    'Meningkatnya kemampuan literasi dan numerasi peserta didik.',
                    'Terlaksananya digitalisasi sekolah dalam manajemen dan pembelajaran.'
                ]
            ])]
        );
    }
}
