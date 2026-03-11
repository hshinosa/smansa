<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportTeachersFromFolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $basePath = base_path('foto-guru');

        if (!File::exists($basePath)) {
            $this->command->error("Folder 'foto-guru' tidak ditemukan di root project!");
            return;
        }

        $this->command->info("Memulai impor foto guru dari: " . $basePath);

        // Bersihkan data lama
        Teacher::query()->delete(); 

        $totalImported = 0;
        $sortOrder = 0;

        // 1. Process individual files in root directory first (Kepala Sekolah, Wakasek)
        $rootFiles = File::files($basePath);
        foreach ($rootFiles as $file) {
            // Skip non-images and special files
            if (!in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'])) {
                continue;
            }
            
            // Skip utility images
            if (in_array($file->getFilename(), ['SMANSA.jpeg', 'UPACARA.jpeg'])) {
                continue;
            }

            $filename = $file->getFilenameWithoutExtension();
            
            // Determine type based on filename
            $mapping = $this->getFolderMapping($filename);
            
            // Extract name from filename
            $name = $this->extractNameFromFilename($filename);
            
            $this->command->line("Memproses file root: <info>{$filename}</info> → {$name}");

            $teacher = Teacher::updateOrCreate(
                ['name' => $name],
                [
                    'type' => $mapping['type'],
                    'department' => $mapping['department'],
                    'position' => $mapping['default_position'],
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                    'nip' => '19' . rand(70, 99) . rand(10, 12) . rand(10, 28) . '200' . rand(1, 9) . rand(100, 999),
                ]
            );

            // Attach Image
            try {
                $teacher->clearMediaCollection('photos');
                $teacher->addMedia($file->getPathname())
                    ->preservingOriginal()
                    ->toMediaCollection('photos');
                $totalImported++;
            } catch (\Exception $e) {
                $this->command->error("Gagal mengimpor foto untuk {$name}: " . $e->getMessage());
            }
        }

        // 2. Process directories
        $directories = File::directories($basePath);
        foreach ($directories as $dirPath) {
            $folderName = basename($dirPath);
            $mapping = $this->getFolderMapping($folderName);
            
            $this->command->line("Memproses folder: <info>{$folderName}</info>");

            $files = File::files($dirPath);
            foreach ($files as $file) {
                // Skip non-images
                if (!in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'])) {
                    continue;
                }

                $filename = $file->getFilenameWithoutExtension();
                
                // Bersihkan nama dari whitespace berlebih atau noise
                $name = trim($filename);
                
                // Tentukan position (jika ada info di nama file)
                $position = $mapping['default_position'];
                $department = $mapping['department'];
                
                // Khusus untuk folder Manajemen, coba tebak jabatan
                if ($mapping['department'] === 'Pimpinan') {
                    if (Str::contains(strtoupper($name), 'KEPALA SEKOLAH')) {
                        $position = 'Kepala Sekolah';
                    }
                }
                
                // Detect Wakasek from name
                if (Str::contains(strtoupper($name), 'WAKASEK') || Str::contains(strtoupper($name), 'WAKIL KEPALA')) {
                    $position = 'Wakasek';
                    // Extract bidang if possible
                    if (preg_match('/WAKASEK\s+(\w+)/i', strtoupper($name), $matches)) {
                        $department = ucfirst(strtolower($matches[1]));
                    }
                }

                if (Str::contains($name, '(') && Str::contains($name, ')')) {
                    // Contoh: "Nama (Guru PKn)"
                    preg_match('/\((.*?)\)/', $name, $matches);
                    if (isset($matches[1])) {
                        // Extract only position type (Guru, Staff, Wakasek, etc)
                        if (Str::contains(strtoupper($matches[1]), 'GURU')) {
                            $position = 'Guru';
                        } elseif (Str::contains(strtoupper($matches[1]), 'STAFF')) {
                            $position = 'Staff';
                        } elseif (Str::contains(strtoupper($matches[1]), 'WAKASEK')) {
                            $position = 'Wakasek';
                        }
                        $name = trim(preg_replace('/\s*\(.*?\)\s*/', '', $name));
                    }
                }

                // Create or Update Teacher
                $teacher = Teacher::updateOrCreate(
                    ['name' => $name],
                    [
                        'type' => $mapping['type'],
                        'department' => $department,
                        'position' => $position,
                        'is_active' => true,
                        'sort_order' => $sortOrder++,
                        // NIP dummy
                        'nip' => '19' . rand(70, 99) . rand(10, 12) . rand(10, 28) . '200' . rand(1, 9) . rand(100, 999),
                    ]
                );

                // Attach Image via Spatie Media Library
                try {
                    // Kosongkan koleksi sebelumnya agar tidak menumpuk jika re-seed
                    $teacher->clearMediaCollection('photos');
                    
                    $teacher->addMedia($file->getPathname())
                        ->preservingOriginal()
                        ->toMediaCollection('photos');
                    
                    $totalImported++;
                } catch (\Exception $e) {
                    $this->command->error("Gagal mengimpor foto untuk {$name}: " . $e->getMessage());
                }
            }
        }

        $this->command->info("Selesai! Berhasil mengimpor {$totalImported} guru/staff.");
    }

    /**
     * Map folder names to database categories
     */
    private function getFolderMapping(string $folderName): array
    {
        $name = strtoupper(trim($folderName));

        // 1. Kepala Sekolah (file individual di root)
        if (Str::contains($name, 'KEPALA SEKOLAH')) {
            return [
                'type' => 'guru',
                'department' => 'Pimpinan',
                'default_position' => 'Kepala Sekolah'
            ];
        }

        // 2. Wakasek (folder dan file individual)
        if (Str::contains($name, 'WAKASEK')) {
            // Try to extract bidang (Kesiswaan, Kurikulum, etc)
            $bidang = 'Umum';
            if (preg_match('/WAKASEK\s+(\w+)/i', $name, $matches)) {
                $bidang = ucfirst(strtolower($matches[1]));
            }
            return [
                'type' => 'guru',
                'department' => $bidang,
                'default_position' => 'Wakasek'
            ];
        }
        
        // 3. Manajemen & Komite
        if (Str::contains($name, 'MANAJEMEN')) {
            return [
                'type' => 'staff',
                'department' => 'Manajemen',
                'default_position' => 'Staff'
            ];
        }

        // 4. Staff & Support
        if (Str::contains($name, 'STAFF TU')) {
            return [
                'type' => 'staff',
                'department' => 'Tata Usaha',
                'default_position' => 'Staff'
            ];
        }
        if (Str::contains($name, 'PERPUSTAKAAN')) {
            return [
                'type' => 'staff',
                'department' => 'Perpustakaan',
                'default_position' => 'Staff'
            ];
        }

        // 5. Counseling (MG BIMBINGAN KONSELING)
        if (Str::contains($name, 'BK') || Str::contains($name, 'BIMBINGAN') || Str::contains($name, 'KONSELING')) {
            return [
                'type' => 'guru',
                'department' => 'Bimbingan Konseling',
                'default_position' => 'Guru'
            ];
        }

        // 6. MGMP Subjects - Normalize department names
        $cleanDept = trim(str_ireplace(['MGMP ', 'MG '], '', $folderName));
        
        // Map folder names to standardized department names
        $departmentMap = [
            // Bahasa
            'BAHASA JEPANG' => 'Bahasa Jepang',
            'BAHASA SUNDA' => 'Bahasa Sunda',
            'BAHASA INDONESIA' => 'Bahasa Indonesia',
            'BAHASA INGGRIS' => 'Bahasa Inggris',
            'B. JEPANG' => 'Bahasa Jepang',
            'B. SUNDA' => 'Bahasa Sunda',
            'B. INDONESIA' => 'Bahasa Indonesia',
            'B. INGGRIS' => 'Bahasa Inggris',
            
            // MIPA
            'BIOLOGI' => 'Biologi',
            'FISIKA' => 'Fisika',
            'KIMIA' => 'Kimia',
            'MATEMATIKA' => 'Matematika',
            'MTK' => 'Matematika',
            'MATH' => 'Matematika',
            
            // IPS
            'EKONOMI' => 'Ekonomi',
            'GEOGRAFI' => 'Geografi',
            'SEJARAH' => 'Sejarah',
            'SOSIOLOGI' => 'Sosiologi',
            
            // Agama & PKN
            'PENDIDIKAN AGAMA ISLAM' => 'Pendidikan Agama Islam',
            'PENDIDIKAN PANCASILA' => 'Pendidikan Pancasila',
            'PAI' => 'Pendidikan Agama Islam',
            'PPKN' => 'Pendidikan Pancasila',
            'PKN' => 'Pendidikan Pancasila',
            
            // Olahraga & Seni
            'PJOK' => 'Penjasorkes',
            'PENJASORKES' => 'Penjasorkes',
            'PENDIDIKAN JASMANI' => 'Penjasorkes',
            'PENDIDIKAN JASMANI OLAHRAGA DAN KESEHATAN' => 'Penjasorkes',
            'PENJAS' => 'Penjasorkes',
            'OLAHRAGA' => 'Penjasorkes',
            
            // PKWU
            'PKWU' => 'Prakarya dan Kewirausahaan',
            'PRAKARYA DAN KEWIRAUSAHAAN' => 'Prakarya dan Kewirausahaan',
            'PRAKARYA' => 'Prakarya dan Kewirausahaan',
            'KEWIRAUSAHAAN' => 'Prakarya dan Kewirausahaan',
            
            // Seni
            'SENI BUDAYA' => 'Seni Budaya',
            'SENI' => 'Seni Budaya',
        ];

        $deptKey = strtoupper($cleanDept);
        if (isset($departmentMap[$deptKey])) {
            $displayName = $departmentMap[$deptKey];
        } else {
            $displayName = ucwords(strtolower($cleanDept));
        }
        
        return [
            'type' => 'guru',
            'department' => $displayName, 
            'default_position' => 'Guru'
        ];
    }

    /**
     * Extract clean name from filename
     * Example: "KEPALA SEKOLAH ( H. Dudi Rohdiana, S.Pd., M.M )" → "H. Dudi Rohdiana, S.Pd., M.M"
     * Example: "WAKASEK KESISWAAN ( Dadang Sofyan, M.Pd. )" → "Dadang Sofyan, M.Pd."
     */
    private function extractNameFromFilename(string $filename): string
    {
        // Check if name is in parentheses
        if (preg_match('/\(\s*(.+?)\s*\)/', $filename, $matches)) {
            return trim($matches[1]);
        }
        
        // Remove position prefixes
        $name = preg_replace('/^(KEPALA SEKOLAH|WAKASEK\s+\w+)\s*-?\s*/i', '', $filename);
        
        return trim($name);
    }
}
