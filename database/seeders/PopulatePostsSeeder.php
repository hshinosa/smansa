<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\Admin; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class PopulatePostsSeeder extends Seeder
{
    public function run()
    {
        Post::truncate();

        // Ensure at least one admin exists for author_id
        $admin = Admin::first();
        if (!$admin) {
            $admin = Admin::create([
                'name' => 'Admin Sekolah',
                'email' => 'admin@smkweb.com',
                'password' => bcrypt('password'),
            ]);
        }

        $posts = [
            [
                'title' => 'PPDB 2025 Resmi Dibuka: Panduan Lengkap Pendaftaran',
                'category' => 'Pengumuman',
                'excerpt' => 'Pendaftaran Peserta Didik Baru (PPDB) Tahun Ajaran 2025/2026 telah dibuka. Simak jadwal, persyaratan, dan tata cara pendaftaran lengkap di sini.',
                'image_name' => 'hero-bg-sman1baleendah.jpeg',
                'featured' => true
            ],
            [
                'title' => 'Tim Basket Putra Raih Juara 1 DBL West Java Series',
                'category' => 'Prestasi',
                'excerpt' => 'Prestasi membanggakan kembali ditorehkan oleh tim basket putra SMAN 1 Baleendah yang berhasil menyabet gelar juara pertama dalam ajang bergengsi DBL.',
                'image_name' => 'hero-bg-sman1baleendah.jpeg',
                'featured' => true
            ],
            [
                'title' => 'Kunjungan Edukasi ke Museum Geologi Bandung',
                'category' => 'Kegiatan',
                'excerpt' => 'Siswa kelas X melakukan kunjungan belajar ke Museum Geologi untuk memperdalam pemahaman materi Geografi dan Sejarah secara langsung.',
                'image_name' => 'anak-sma-programstudi.png',
                'featured' => true
            ],
            [
                'title' => 'Jadwal Ujian Akhir Semester Ganjil 2024/2025',
                'category' => 'Pengumuman',
                'excerpt' => 'Berikut adalah jadwal lengkap pelaksanaan Penilaian Akhir Semester (PAS) Ganjil yang akan dilaksanakan mulai tanggal 2 Desember 2024.',
                'image_name' => 'program-ips.jpg', 
                'featured' => false
            ],
            [
                'title' => 'Siswa SMAN 1 Baleendah Lolos Seleksi Paskibraka Provinsi',
                'category' => 'Prestasi',
                'excerpt' => 'Dua siswa terbaik sekolah berhasil lolos seleksi ketat untuk menjadi anggota Pasukan Pengibar Bendera Pusaka (Paskibraka) tingkat Provinsi Jawa Barat.',
                'image_name' => 'hero-bg-sman1baleendah.jpeg',
                'featured' => false
            ],
            [
                'title' => 'Workshop Literasi Digital: Cerdas Bermedia Sosial',
                'category' => 'Kegiatan',
                'excerpt' => 'OSIS mengadakan workshop literasi digital untuk membekali siswa dengan kemampuan memilah informasi dan etika bermedia sosial.',
                'image_name' => 'anak-sma.png',
                'featured' => false
            ],
            [
                'title' => 'Alumni Berbagi: Sukses Berkarir di BUMN',
                'category' => 'Alumni',
                'excerpt' => 'Sesi sharing inspiratif bersama alumni angkatan 2010 yang kini sukses meniti karir di salah satu perusahaan BUMN terkemuka.',
                'image_name' => 'keluarga-besar-sman1-baleendah.png',
                'featured' => false
            ],
            [
                'title' => 'Peringatan Hari Guru Nasional berlangsung Meriah',
                'category' => 'Berita',
                'excerpt' => 'Perayaan Hari Guru di SMAN 1 Baleendah diisi dengan berbagai penampilan seni dari siswa sebagai wujud terima kasih kepada para pendidik.',
                'image_name' => 'panen-karya-sman1-baleendah.jpg',
                'featured' => false
            ],
            [
                'title' => 'Sosialisasi SNMPTN dan SBMPTN 2025',
                'category' => 'Akademik',
                'excerpt' => 'Bimbingan Konseling (BK) mengadakan sosialisasi jalur masuk perguruan tinggi negeri untuk siswa kelas XII.',
                'image_name' => 'anak-sma-programstudi.png',
                'featured' => false
            ],
            [
                'title' => 'Pengumuman Libur Awal Ramadhan 1446 H',
                'category' => 'Pengumuman',
                'excerpt' => 'Berdasarkan kalender pendidikan, kegiatan belajar mengajar akan diliburkan pada awal bulan suci Ramadhan. Simak tanggal lengkapnya.',
                'image_name' => 'hero-bg-sman1baleendah.jpeg',
                'featured' => false
            ]
        ];

        $smansaPath = base_path('foto-guru/SMANSA.jpeg');

        foreach ($posts as $index => $data) {
            $post = Post::create([
                'title' => $data['title'],
                'slug' => Str::slug($data['title']) . '-' . rand(100, 999), // Unique slug
                'excerpt' => $data['excerpt'],
                'content' => '<p>' . $data['excerpt'] . ' Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>',
                'category' => $data['category'],
                'status' => 'published',
                'author_id' => $admin->id,
                'published_at' => now()->subDays($index),
                'is_featured' => $data['featured'],
                'views_count' => rand(50, 500),
            ]);

            // Attach SMANSA.jpeg as requested
            if (File::exists($smansaPath)) {
                $post->addMedia($smansaPath)
                    ->preservingOriginal()
                    ->toMediaCollection('featured');
            }
        }

        $this->command->info('Posts populated successfully.');
    }
}
