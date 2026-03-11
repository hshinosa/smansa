<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\File;

class PopulateProgramsTableSeeder extends Seeder
{
    public function run()
    {
        $fotoGuruPath = base_path('foto-guru');
        $smansaPath = $fotoGuruPath . DIRECTORY_SEPARATOR . 'SMANSA.jpeg';

        // 1. Update Hero Section Title
        $hero = SiteSetting::updateOrCreate(
            ['section_key' => 'hero_program'],
            ['content' => [
                'title' => 'Program Sekolah',
                'subtitle' => 'Membangun karakter dan kompetensi siswa melalui berbagai inisiatif positif.',
                'image' => '/images/hero-bg-sman1baleendah.jpeg'
            ]]
        );

        if (File::exists($smansaPath)) {
            $hero->clearMediaCollection('hero_program_bg');
            $hero->addMedia($smansaPath)->preservingOriginal()->toMediaCollection('hero_program_bg');
        }

        // 2. Program Studi (Existing)
        $programs = [
            [
                'title' => 'MIPA',
                'description' => 'Program Matematika dan Ilmu Pengetahuan Alam dengan fokus pada sains, teknologi, dan riset ilmiah.',
                'image_name' => 'anak-sma-programstudi.png',
                'icon_name' => 'Microscope',
                'link' => '/akademik/program-studi/mipa',
                'category' => 'Program Studi',
                'sort_order' => 1
            ],
            [
                'title' => 'IPS',
                'description' => 'Program Ilmu Pengetahuan Sosial yang mempelajari dinamika masyarakat, ekonomi, dan sejarah.',
                'image_name' => 'anak-sma-programstudi.png', 
                'icon_name' => 'Globe',
                'link' => '/akademik/program-studi/ips',
                'category' => 'Program Studi',
                'sort_order' => 2
            ],
            [
                'title' => 'Bahasa',
                'description' => 'Program Bahasa dan Budaya untuk penguasaan komunikasi global dan literasi.',
                'image_name' => 'anak-sma-programstudi.png',
                'icon_name' => 'BookOpen',
                'link' => '/akademik/program-studi/bahasa',
                'category' => 'Program Studi',
                'sort_order' => 3
            ],
            // 3. Program Sekolah (Non-Prodi) - NEW
            [
                'title' => 'Sekolah Adiwiyata',
                'description' => 'Program sekolah berbudaya lingkungan untuk menciptakan kesadaran pelestarian alam.',
                'path' => $smansaPath,
                'icon_name' => 'Leaf',
                'link' => '#',
                'category' => 'Program Unggulan',
                'sort_order' => 4
            ],
            [
                'title' => 'Sekolah Ramah Anak',
                'description' => 'Menciptakan lingkungan belajar yang aman, nyaman, dan menyenangkan bagi seluruh siswa.',
                'path' => $smansaPath,
                'icon_name' => 'Heart',
                'link' => '#',
                'category' => 'Program Unggulan',
                'sort_order' => 5
            ],
            [
                'title' => 'Literasi Digital',
                'description' => 'Penguatan kemampuan siswa dalam memanfaatkan teknologi digital secara bijak dan produktif.',
                'path' => $smansaPath,
                'icon_name' => 'Cpu',
                'link' => '#',
                'category' => 'Program Unggulan',
                'sort_order' => 6
            ]
        ];

        foreach ($programs as $data) {
            $program = Program::updateOrCreate(
                ['title' => $data['title']],
                [
                    'description' => $data['description'],
                    'icon_name' => $data['icon_name'],
                    'link' => $data['link'],
                    'category' => $data['category'],
                    'is_featured' => true,
                    'sort_order' => $data['sort_order']
                ]
            );

            // Use SMANSA.jpeg for all programs as requested
            if (File::exists($smansaPath)) {
                $program->clearMediaCollection('program_images');
                $program->addMedia($smansaPath)->preservingOriginal()->toMediaCollection('program_images');
            }
        }
    }
}
