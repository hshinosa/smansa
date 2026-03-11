<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gallery;
use Illuminate\Support\Facades\File;

class BetterFixGalleryImagesSeeder extends Seeder
{
    public function run()
    {
        $galleries = Gallery::all();
        
        // Mapping keywords to files present in public/images
        $imageMap = [
            'Upacara' => 'keluarga-besar-sman1-baleendah.png',
            'Belajar' => 'anak-sma.png',
            'Komputer' => 'anak-sma-programstudi.png',
            'Perpustakaan' => 'anak-sma-programstudi.png',
            'Basket' => 'panen-karya-sman1-baleendah.jpg',
            'Pentas' => 'hero-bg-sman1baleendah.jpeg',
            'IPA' => 'anak-sma-programstudi.png',
            'OSIS' => 'struktur-organisasi-sman1-baleendah.jpg',
            'Lapangan' => 'hero-bg-sman1baleendah.jpeg',
            'Pramuka' => 'panen-karya-sman1-baleendah.jpg',
            'Wisuda' => 'keluarga-besar-sman1-baleendah.png',
            'Bahasa' => 'anak-sma.png',
            'Juara' => 'panen-karya-sman1-baleendah.jpg',
            'Rapat' => 'struktur-organisasi-sman1-baleendah.jpg',
            'Keluarga' => 'keluarga-besar-sman1-baleendah.png',
        ];

        $defaultImage = 'hero-bg-sman1baleendah.jpeg';

        foreach ($galleries as $gallery) {
            $imageName = $defaultImage;
            
            // Try to match title keywords
            foreach ($imageMap as $keyword => $file) {
                if (stripos($gallery->title, $keyword) !== false) {
                    $imageName = $file;
                    break;
                }
            }

            $sourcePath = public_path("images/{$imageName}");

            if (File::exists($sourcePath)) {
                $this->command->info("Migrating Gallery: {$gallery->title} -> {$imageName}");
                
                // Clear existing media to avoid duplicates
                $gallery->clearMediaCollection('images');
                
                // Add new media
                $gallery->addMedia($sourcePath)
                    ->preservingOriginal()
                    ->toMediaCollection('images');
                
                // Update fallback URL
                // Note: We use the direct path here as a fallback, but the controller uses media URL
                $gallery->url = "/images/{$imageName}"; 
                $gallery->save();
            } else {
                $this->command->warn("Source image not found: {$sourcePath}");
            }
        }
    }
}
