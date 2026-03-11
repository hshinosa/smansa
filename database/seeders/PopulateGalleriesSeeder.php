<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gallery;
use Illuminate\Support\Facades\File;

class PopulateGalleriesSeeder extends Seeder
{
    public function run()
    {
        $fotoGuruPath = base_path('foto-guru');
        $smansaPath = $fotoGuruPath . DIRECTORY_SEPARATOR . 'SMANSA.jpeg';

        // Clear existing data
        Gallery::truncate();

        $galleryData = [
            'Upacara Bendera Hari Senin' => 'keluarga-besar-sman1-baleendah.png',
            'Kegiatan Belajar di Kelas' => 'anak-sma.png',
            'Praktikum Laboratorium Komputer' => 'anak-sma-programstudi.png',
            'Suasana Perpustakaan Sekolah' => 'anak-sma-programstudi.png',
            'Latihan Ekstrakurikuler Basket' => 'panen-karya-sman1-baleendah.jpg',
            'Pentas Seni Siswa' => 'hero-bg-sman1baleendah.jpeg',
            'Praktikum Laboratorium IPA' => 'anak-sma-programstudi.png',
            'Rapat Koordinasi OSIS' => 'struktur-organisasi-sman1-baleendah.jpg',
            'Fasilitas Lapangan Olahraga' => 'hero-bg-sman1baleendah.jpeg',
            'Kegiatan Kepramukaan' => 'panen-karya-sman1-baleendah.jpg',
            'Wisuda Angkatan 2024' => 'keluarga-besar-sman1-baleendah.png',
            'Kelas Bahasa Asing' => 'anak-sma.png',
        ];

        foreach ($galleryData as $title => $imageName) {
            // Create gallery with temporary URL
            $gallery = Gallery::create([
                'title' => $title,
                'description' => "Dokumentasi kegiatan {$title} di SMAN 1 Baleendah.",
                'type' => 'photo',
                'category' => 'Kegiatan',
                'is_featured' => true,
                'url' => 'temp', // Temporary placeholder
            ]);

            // ALWAYS use Smansa for gallery as requested
            if (File::exists($smansaPath)) {
                $gallery->addMedia($smansaPath)
                    ->preservingOriginal()
                    ->toMediaCollection('images');
            } else {
                $sourcePath = public_path("images/{$imageName}");
                if (File::exists($sourcePath)) {
                    $gallery->addMedia($sourcePath)
                        ->preservingOriginal()
                        ->toMediaCollection('images');
                }
            }

            // Update URL to actual Media Library URL
            $media = $gallery->getFirstMedia('images');
            if ($media) {
                $gallery->update(['url' => $media->getUrl()]);
            }
        }
        
        $this->command->info('Galleries populated with ' . count($galleryData) . ' items.');
    }
}
