<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Alumni;
use Illuminate\Support\Facades\File;

class PopulateAlumniSeeder extends Seeder
{
    public function run()
    {
        Alumni::truncate();

        $alumniData = [
            // FEATURED - dengan video YouTube
            [
                'name' => 'Dr. Ahmad Hidayat',
                'graduation_year' => 2005,
                'testimonial' => 'SMAN 1 Baleendah memberikan fondasi akademik yang kuat dan mengajarkan nilai-nilai karakter yang saya bawa hingga masa dewasa. Saat ini sebagai Dosen di Universitas Indonesia dengan latar belakang S3 Pendidikan Matematika.',
                'video_url' => 'https://www.youtube.com/watch?v=jtqI3aP82wk',
                'video_source' => 'youtube',
                'content_type' => 'video',
                'is_featured' => true
            ],
            [
                'name' => 'Sarah Wijaya',
                'graduation_year' => 2010,
                'testimonial' => 'Lingkungan sekolah yang mendukung dan guru-guru yang berdedikasi membantu saya mengejar mimpi menjadi pengusaha. Kini sebagai Entrepreneur dan Founder Tech Startup.',
                'video_url' => 'https://www.youtube.com/watch?v=jtqI3aP82wk',
                'video_source' => 'youtube',
                'content_type' => 'video',
                'is_featured' => true
            ],
            
            // NON-FEATURED - dengan gambar anak-sma.png
            [
                'name' => 'Budi Santoso',
                'graduation_year' => 2015,
                'testimonial' => 'Fasilitas dan kurikulum yang berkualitas mempersiapkan saya untuk bersaing di kancah internasional. Sekarang bekerja sebagai Engineer di Perusahaan Teknologi Global.',
                'image_name' => 'anak-sma.png',
                'content_type' => 'text',
                'is_featured' => false
            ],
            [
                'name' => 'Putri Ayu',
                'graduation_year' => 2018,
                'testimonial' => 'Disiplin dan kerja keras yang diajarkan di sekolah menjadi pondasi sukses saya dalam pendidikan kedokteran. Saat ini praktik sebagai Dokter Spesialis Anak.',
                'image_name' => 'anak-sma.png',
                'content_type' => 'text',
                'is_featured' => false
            ],
            [
                'name' => 'Rizky Pratama',
                'graduation_year' => 2012,
                'testimonial' => 'Organisasi kesiswaan di SMAN 1 Baleendah melatih kepemimpinan saya sejak dini. Kini menjabat sebagai Manajer di BUMN dengan latar belakang S2 Manajemen Bisnis.',
                'image_name' => 'anak-sma.png',
                'content_type' => 'text',
                'is_featured' => false
            ],
            [
                'name' => 'Ani Lestari',
                'graduation_year' => 2019,
                'testimonial' => 'Ekstrakurikuler jurnalistik sekolah membuka jalan karir saya di dunia media. Sekarang bekerja sebagai Jurnalis di TV Nasional.',
                'image_name' => 'anak-sma.png',
                'content_type' => 'text',
                'is_featured' => false
            ]
        ];

        $studentPhotoPath = public_path('images/anak-sma.png');
        $secondaryPhotoPath = public_path('images/anak-sma-programstudi.png');

        foreach ($alumniData as $data) {
            unset($data['image_name']);
            
            $data['is_published'] = true;
            $data['sort_order'] = 0;

            $alumni = Alumni::create($data);

            // Use student photo (anak-sma.png) for alumni as requested
            if ($data['content_type'] === 'text' && File::exists($studentPhotoPath)) {
                $alumni->addMedia($studentPhotoPath)
                    ->preservingOriginal()
                    ->toMediaCollection('testimonial_images');

                if (File::exists($secondaryPhotoPath)) {
                    $alumni->addMedia($secondaryPhotoPath)
                        ->preservingOriginal()
                        ->toMediaCollection('testimonial_images');
                }
            }
        }

        $this->command->info('Alumni populated successfully with video and image content.');
    }
}
