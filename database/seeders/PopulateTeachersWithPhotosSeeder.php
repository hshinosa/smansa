<?php

namespace Database\Seeders;

use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PopulateTeachersWithPhotosSeeder extends Seeder
{
    private $teacherNames = [
        'Dra. Hj. Rina Herawati',
        'Drs. H. Ahmad Fauzi',
        'Hj. Siti Nurjanah, S.Pd',
        'Drs. Bambang Sutrisno',
        'Dra. Sri Wahyuni',
        'Ahmad Rizki, S.Pd',
        'Siti Aminah, S.Pd',
        'Drs. H. Suparman',
        'Dra. Hj. Dewi Sartika',
        'H. Dudi Rohdiana, S.Pd., M.M',
        'Yayuk Soneka, S.Pd',
        'Erfan Rifansyah, S.Pd',
        'Pancasariwarni, M.Pd',
        'Riki Khaerul Anwar, S.Pd',
        'Dra. Menti Nursapriyantini, M.M.Pd',
        'Dra. Rina Ristiawati',
        'Galih Djayadibrata, S.I.Kom',
        'Dra. Hj. Alis Marliati',
        'Kusrawan, S.Pd',
        'Dadang Sofyan, M.Pd',
        'Raden Bayu Aji Wicaksono, S.Pd',
        'Oki Ilman Nafian, S.Pd',
        'Elan Jaelani, S.Pd',
        'Risalatulhaq, S.Pd',
        'Erwan Risnawan, S.Pd',
        'Tety Kurniawati, S.Pd',
        'Sri Susantini, S.Pd',
        'Turhayani, S.Pd',
        'Anwar Huda, S.Pd',
        'Dra. Mona Haldrina Hikmat',
        'Wakil Kepala Sekolah 1',
        'Wakil Kepala Sekolah 2',
        'Wakil Kepala Sekolah 3',
        'Wakil Kepala Sekolah 4',
        'Guru Bahasa Indonesia 1',
        'Guru Bahasa Indonesia 2',
        'Guru Matematika 1',
        'Guru Matematika 2',
        'Guru IPA 1',
        'Guru IPA 2',
        'Guru IPS 1',
        'Guru IPS 2',
        'Guru Bahasa Inggris 1',
        'Guru Bahasa Inggris 2',
        'Guru PJOK 1',
        'Guru PJOK 2',
        'Guru Seni Budaya 1',
        'Guru Seni Budaya 2',
        'Guru Prakarya 1',
        'Guru Prakarya 2',
        'Guru BK 1',
        'Guru BK 2',
        'Guru Agama Islam 1',
        'Guru Agama Islam 2',
        'Guru PKN 1',
        'Guru PKN 2',
        'Guru Sejarah 1',
        'Guru Sejarah 2',
        'Guru Geografi 1',
        'Guru Geografi 2',
        'Guru Ekonomi 1',
        'Guru Ekonomi 2',
        'Guru Sosiologi 1',
        'Guru Sosiologi 2',
    ];

    public function run(): void
    {
        $this->command->info('Populating teachers with photos...');

        $sourceFolder = base_path('data_smansa/SMAN 1 BALEENDAH - 2026/FOTO GURU');

        if (! File::isDirectory($sourceFolder)) {
            $this->command->error("Source folder not found: {$sourceFolder}");

            return;
        }

        $images = File::glob($sourceFolder.'/*.jpg');
        $totalImages = count($images);

        if ($totalImages === 0) {
            $this->command->error('No images found in source folder');

            return;
        }

        $this->command->info("Found {$totalImages} teacher photos");

        $teacherNamesShuffled = $this->teacherNames;
        shuffle($teacherNamesShuffled);

        $imageIndex = 0;
        $created = 0;

        foreach ($teacherNamesShuffled as $index => $name) {
            if ($imageIndex >= $totalImages) {
                $imageIndex = 0;
            }

            $sourceImage = $images[$imageIndex];
            $imageName = basename($sourceImage);
            $destPath = 'teachers/'.Str::random(20).'_'.$imageName;

            $imageContent = File::get($sourceImage);
            $image = imagecreatefromstring($imageContent);

            $rotated = imagerotate($image, 90, 0);

            $tempFile = tempnam(sys_get_temp_dir(), 'teacher_').'.jpg';
            imagejpeg($rotated, $tempFile, 90);

            Storage::disk('public')->put($destPath, File::get($tempFile));

            imagedestroy($image);
            imagedestroy($rotated);
            unlink($tempFile);

            $fullPath = Storage::disk('public')->path($destPath);

            $teacher = Teacher::create([
                'name' => $name,
                'type' => 'guru',
                'position' => 'Guru Mata Pelajaran',
                'department' => 'Kurikulum',
                'nip' => 'NIP-'.str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'email' => strtolower(Str::slug($name)).'@sman1baleendah.sch.id',
                'phone' => '08'.rand(100000000, 999999999),
                'bio' => $this->generateRandomBio(),
                'sort_order' => $index,
                'is_active' => true,
            ]);

            $teacher->clearMediaCollection('photos');
            $teacher->addMedia($fullPath)
                ->toMediaCollection('photos');

            $created++;
            $this->command->info("Created: {$name}");

            $imageIndex++;
        }

        $this->command->info("Successfully created {$created} teachers with photos!");
    }

    private function generateRandomBio(): string
    {
        $bios = [
            'Berdedikasi tinggi dalam mendidik dan membimbing siswa untuk mencapai potensi terbaik mereka.',
            'Pengalaman mengajar lebih dari 10 tahun dengan fokus pada pengembangan karakter siswa.',
            'Selalu berkomitmen untuk menciptakan lingkungan belajar yang inspiratif dan menyenangkan.',
            'Aktif mengikuti pelatihan dan pengembangan profesi untuk meningkatkan kualitas pembelajaran.',
            'Memiliki passion yang kuat dalam membantu siswa meraih prestasi akademik dan non-akademik.',
            'Berdedikasi untuk mencetak generasi yang unggul dalam prestasi dan berakhlak mulia.',
            'Fokus pada pembelajaran interaktif yang mendorong kreativitas dan berpikir kritis siswa.',
            'Selalu berusaha menjadi teladan yang baik bagi siswa dalam sikap dan perilaku.',
        ];

        return $bios[array_rand($bios)];
    }
}
