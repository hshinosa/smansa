<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use Illuminate\Support\Facades\File;

class PopulateTeachersSeeder extends Seeder
{
    public function run()
    {
        // Bersihkan data lama agar tidak duplikat
        Teacher::truncate();

        // Data berdasarkan logika MGMP_GROUPS di GuruStaffPage.jsx
        // Type DB: 'guru' atau 'staff' only
        $teachersData = [
            // Pimpinan & Manajemen
            [
                'name' => 'Drs. H. Dede Amar, M.M.Pd.', 
                'position' => 'Kepala Sekolah', 
                'department' => 'Manajemen', 
                'type' => 'guru'
            ],
            [
                'name' => 'Dra. Hj. Elis Nurhayati', 
                'position' => 'Wakil Kepala Sekolah Bidang Kurikulum', 
                'department' => 'Manajemen', 
                'type' => 'guru'
            ],
            [
                'name' => 'Asep Saepudin, S.Pd.', 
                'position' => 'Wakil Kepala Sekolah Bidang Kesiswaan', 
                'department' => 'Manajemen', 
                'type' => 'guru'
            ],
            
            // MIPA
            ['name' => 'Budi Santoso, S.Pd.', 'position' => 'Guru Matematika', 'department' => 'Matematika', 'type' => 'guru'],
            ['name' => 'Siti Aminah, S.Si.', 'position' => 'Guru Fisika', 'department' => 'Fisika', 'type' => 'guru'],
            ['name' => 'Rina Wati, S.Pd.', 'position' => 'Guru Biologi', 'department' => 'Biologi', 'type' => 'guru'],
            ['name' => 'Agus Setiawan, S.Pd.', 'position' => 'Guru Kimia', 'department' => 'Kimia', 'type' => 'guru'],

            // IPS
            ['name' => 'Dr. Bambang Sutrisno', 'position' => 'Guru Sosiologi', 'department' => 'Sosiologi', 'type' => 'guru'],
            ['name' => 'Ani Yuliani, S.Pd.', 'position' => 'Guru Geografi', 'department' => 'Geografi', 'type' => 'guru'],
            ['name' => 'Eko Prasetyo, S.Pd.', 'position' => 'Guru Ekonomi', 'department' => 'Ekonomi', 'type' => 'guru'],
            ['name' => 'Dewi Sartika, S.Pd.', 'position' => 'Guru Sejarah', 'department' => 'Sejarah', 'type' => 'guru'],

            // Bahasa & Budaya
            ['name' => 'Cecep Supriatna, S.Pd.', 'position' => 'Guru Bahasa Sunda', 'department' => 'Bahasa Sunda', 'type' => 'guru'],
            ['name' => 'Linda Kusuma, S.S.', 'position' => 'Guru Bahasa Inggris', 'department' => 'Bahasa Inggris', 'type' => 'guru'],
            ['name' => 'Ahmad Fauzi, S.Pd.', 'position' => 'Guru Bahasa Indonesia', 'department' => 'Bahasa Indonesia', 'type' => 'guru'],
            ['name' => 'Hiroshi Tanaka, M.Ed.', 'position' => 'Guru Bahasa Jepang', 'department' => 'Bahasa Asing', 'type' => 'guru'],

            // Vokasi & Teknologi
            ['name' => 'Iwan Fals, S.Kom.', 'position' => 'Guru TIK', 'department' => 'Teknologi Informasi', 'type' => 'guru'],
            ['name' => 'Susi Susanti, S.T.', 'position' => 'Guru Prakarya', 'department' => 'Prakarya', 'type' => 'guru'],

            // Penjasorkes & Seni
            ['name' => 'Taufik Hidayat, S.Pd.', 'position' => 'Guru Olahraga', 'department' => 'Olahraga', 'type' => 'guru'],
            ['name' => 'Sherina Munaf, S.Sn.', 'position' => 'Guru Seni Budaya', 'department' => 'Seni Budaya', 'type' => 'guru'],

            // Pendidikan Agama & PKN
            ['name' => 'Ust. Abdullah, S.Ag.', 'position' => 'Guru PAI', 'department' => 'Pendidikan Agama', 'type' => 'guru'],
            ['name' => 'Ratna Sari, S.Pd.', 'position' => 'Guru PPKn', 'department' => 'PPKn', 'type' => 'guru'],

            // Tata Usaha & Staff
            ['name' => 'Ujang Solihin', 'position' => 'Kepala Tata Usaha', 'department' => 'Tata Usaha', 'type' => 'staff'],
            ['name' => 'Wawan Hendrawan', 'position' => 'Staff Administrasi', 'department' => 'Administrasi', 'type' => 'staff'],
            ['name' => 'Bu Yeti', 'position' => 'Guru BK', 'department' => 'Bimbingan Konseling', 'type' => 'guru'], // BK sering masuk Staff group di UI logic
        ];

        // Placeholder images loop
        $localImages = [
            'keluarga-besar-sman1-baleendah.png', // Fallback for profile
            'panen-karya-sman1-baleendah.jpg',
        ];

        $counter = 0;

        $smansaPath = base_path('foto-guru/SMANSA.jpeg');

        foreach ($teachersData as $data) {
            $teacher = Teacher::create([
                'name' => $data['name'],
                'type' => $data['type'], 
                'position' => $data['position'],
                'department' => $data['department'],
                'nip' => '19' . rand(70, 99) . rand(10, 12) . rand(10, 30) . '200' . rand(1, 9),
                'is_active' => true,
                'sort_order' => $counter++,
            ]);

            // Attach SMANSA.jpeg for Kepala Sekolah as a sample placeholder if original not available
            if ($data['position'] === 'Kepala Sekolah' && File::exists($smansaPath)) {
                $teacher->addMedia($smansaPath)
                    ->preservingOriginal()
                    ->toMediaCollection('photos');
            }
        }

        $this->command->info('Teachers populated successfully (text only, images handled by UI Avatars).');
    }
}
