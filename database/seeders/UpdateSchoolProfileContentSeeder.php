<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolProfileSetting;
use Illuminate\Support\Facades\File;

class UpdateSchoolProfileContentSeeder extends Seeder
{
    public function run()
    {
        $fotoGuruPath = base_path('foto-guru');

        // 1. HERO HISTORY
        $hero = SchoolProfileSetting::updateOrCreate(
            ['section_key' => 'hero_history'],
            ['content' => json_encode([
                'title' => 'Profil & Sejarah SMAN 1 Baleendah',
                'image_url' => '/images/hero-bg-sman1baleendah.jpeg'
            ])]
        );
        
        // Migrate Hero Image
        $heroBgPath = $fotoGuruPath . DIRECTORY_SEPARATOR . 'SMANSA.jpeg';
        if (File::exists($heroBgPath)) {
            $hero->clearMediaCollection('hero_history_bg');
            $hero->addMedia($heroBgPath)
                ->preservingOriginal()
                ->toMediaCollection('hero_history_bg');
        }

        // 2. HISTORY TIMELINE
        SchoolProfileSetting::updateOrCreate(
            ['section_key' => 'history'],
            ['content' => json_encode([
                'title' => 'Jejak Langkah Kami',
                'description_html' => '<p>Perjalanan panjang SMAN 1 Baleendah dalam mencerdaskan kehidupan bangsa dimulai sejak tahun 1975.</p>',
                'timeline' => [
                    [
                        'year' => "1975",
                        'title' => "Awal Pendirian",
                        'description' => "Berdiri sebagai sekolah filial (kelas jauh) untuk memenuhi kebutuhan pendidikan di wilayah Baleendah.",
                        'side' => "left"
                    ],
                    [
                        'year' => "1980",
                        'title' => "Penegerian",
                        'description' => "Resmi berdiri sendiri sebagai SMAN 1 Baleendah dengan SK Menteri Pendidikan, menandai era baru kemandirian.",
                        'side' => "right"
                    ],
                    [
                        'year' => "2010",
                        'title' => "Pengembangan Fasilitas",
                        'description' => "Revitalisasi gedung utama dan pembangunan Masjid sekolah sebagai pusat pembentukan karakter siswa.",
                        'side' => "left"
                    ],
                    [
                        'year' => "Sekarang",
                        'title' => "Era Prestasi",
                        'description' => "Menjadi Sekolah Penggerak dan meraih predikat Sekolah Adiwiyata Tingkat Provinsi/Nasional.",
                        'side' => "right"
                    ]
                ]
            ])]
        );

        // 3. FACILITIES
        $facilities = SchoolProfileSetting::updateOrCreate(
            ['section_key' => 'facilities'],
            ['content' => json_encode([
                'title' => 'Lingkungan Belajar Modern',
                'description' => 'Fasilitas lengkap yang mendukung pengembangan akademik dan karakter siswa.',
                'items' => [
                    ['name' => "Lab Komputer", 'image' => "temp"],
                    ['name' => "Perpustakaan", 'image' => "temp"],
                    ['name' => "Masjid Sekolah", 'image' => "temp"],
                    ['name' => "Lapangan Olahraga", 'image' => "temp"],
                    ['name' => "Ruang Kelas Modern", 'image' => "temp"]
                ]
            ])]
        );

        // User requested: all facility items use SMANSA.jpeg
        $smansaPath = base_path('foto-guru/SMANSA.jpeg');
        if (File::exists($smansaPath)) {
            // Main Collection
            $facilities->clearMediaCollection('facilities_images');
            $facilities->addMedia($smansaPath)->preservingOriginal()->toMediaCollection('facilities_images');

            // Item-specific collections as per frontend mapping
            foreach (range(0, 4) as $index) {
                $collectionName = "facilities_item_{$index}";
                $facilities->clearMediaCollection($collectionName);
                $facilities->addMedia($smansaPath)->preservingOriginal()->toMediaCollection($collectionName);
            }
        }
    }
}
