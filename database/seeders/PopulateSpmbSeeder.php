<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SpmbSetting;
use Illuminate\Support\Facades\File;

class PopulateSpmbSeeder extends Seeder
{
    public function run()
    {
        // Pengaturan Umum / Hero
        $general = SpmbSetting::updateOrCreate(
            ['section_key' => 'pengaturan_umum'],
            ['content' => [
                'title' => 'Penerimaan Peserta Didik Baru (PPDB) 2025/2026',
                'description_html' => 'Selamat datang di portal resmi PPDB SMA Negeri 1 Baleendah. Bergabunglah bersama kami untuk mewujudkan generasi unggul, berprestasi, dan berkarakter mulia.',
                'is_registration_open' => true,
                'registration_year' => '2025/2026',
                'announcement_text' => 'Pendaftaran Jalur Zonasi dan Prestasi telah dibuka. Silakan lengkapi berkas Anda sebelum tanggal 10 Juli 2025.',
                'banner_image_url' => 'hero-bg-sman1baleendah.jpeg', // Reference string
                'whatsapp_number' => '628123456789',
                'video_guide_url' => 'https://www.youtube.com/watch?v=sample'
            ]]
        );
        $this->attachMedia($general, 'banner_image', 'hero-bg-sman1baleendah.jpeg');

        // Jalur Pendaftaran
        SpmbSetting::updateOrCreate(
            ['section_key' => 'jalur_pendaftaran'],
            ['content' => [
                'items' => [
                    [
                        'label' => 'Jalur Zonasi',
                        'quota' => '50%',
                        'description' => 'Diperuntukkan bagi calon peserta didik yang berdomisili di dalam wilayah zonasi yang telah ditetapkan.',
                        'requirements' => ['Kartu Keluarga (min. 1 tahun)', 'Akta Kelahiran', 'Titik Koordinat Rumah']
                    ],
                    [
                        'label' => 'Jalur Prestasi',
                        'quota' => '25%',
                        'description' => 'Bagi siswa dengan capaian akademik (nilai rapor) atau prestasi kejuaraan/lomba minimal tingkat kota/kabupaten.',
                        'requirements' => ['Nilai Rapor Semester 1-5', 'Sertifikat Kejuaraan', 'Surat Keterangan Prestasi']
                    ],
                    [
                        'label' => 'Jalur Afirmasi',
                        'quota' => '15%',
                        'description' => 'Bagi calon peserta didik dari keluarga ekonomi tidak mampu dan penyandang disabilitas.',
                        'requirements' => ['KIP/PKH/KKS', 'Surat Keterangan Tidak Mampu', 'Data DTKS']
                    ],
                    [
                        'label' => 'Perpindahan Tugas',
                        'quota' => '5%',
                        'description' => 'Bagi calon peserta didik yang mengikuti kepindahan tugas orang tua/wali.',
                        'requirements' => ['Surat Keputusan Pindah Tugas', 'Surat Keterangan Domisili']
                    ]
                ]
            ]]
        );

        // Jadwal Penting
        SpmbSetting::updateOrCreate(
            ['section_key' => 'jadwal_penting'],
            ['content' => [
                'items' => [
                    ['title' => 'Pendaftaran Tahap 1', 'date' => '2025-06-03', 'description' => 'Afirmasi, Perpindahan Tugas, Prestasi'],
                    ['title' => 'Verifikasi Data', 'date' => '2025-06-05', 'description' => 'Verifikasi berkas oleh panitia'],
                    ['title' => 'Pengumuman Tahap 1', 'date' => '2025-06-20', 'description' => 'Hasil seleksi tahap 1'],
                    ['title' => 'Pendaftaran Tahap 2', 'date' => '2025-06-25', 'description' => 'Jalur Zonasi'],
                    ['title' => 'Daftar Ulang', 'date' => '2025-07-10', 'description' => 'Bagi siswa yang diterima'],
                ]
            ]]
        );

        // FAQ (Expanded)
        SpmbSetting::updateOrCreate(
            ['section_key' => 'faq'],
            ['content' => [
                'title' => 'Pertanyaan yang Sering Diajukan (FAQ)',
                'description' => 'Temukan jawaban untuk pertanyaan umum seputar PPDB SMAN 1 Baleendah di sini.',
                'items' => [
                    [
                        'question' => 'Bagaimana cara mendaftar secara online?', 
                        'answer' => 'Silakan klik tombol "Daftar Sekarang" di halaman ini yang akan mengarahkan Anda ke portal resmi PPDB Jawa Barat. Buat akun menggunakan NISN dan ikuti langkah-langkah pengisian formulir.'
                    ],
                    [
                        'question' => 'Apakah bisa mendaftar dua jalur sekaligus?', 
                        'answer' => 'Tidak, pada satu tahap pendaftaran hanya boleh memilih satu jalur. Namun, jika tidak lolos pada Tahap 1 (Afirmasi/Prestasi/Perpindahan), Anda dapat mendaftar kembali di Tahap 2 (Zonasi).'
                    ],
                    [
                        'question' => 'Bagaimana jika Kartu Keluarga (KK) belum satu tahun?', 
                        'answer' => 'Sesuai aturan, KK harus diterbitkan minimal 1 tahun sebelum tanggal pendaftaran PPDB. Jika ada perubahan data (penambahan anggota keluarga) yang menyebabkan tanggal KK baru, harap lampirkan fotokopi KK lama sebagai bukti.'
                    ],
                    [
                        'question' => 'Berapa passing grade tahun lalu?', 
                        'answer' => 'Passing grade bervariasi setiap tahun tergantung jumlah dan kualitas pendaftar. Untuk jalur zonasi, jarak terjauh tahun lalu sekitar 1.5 km. Untuk prestasi rapor, rata-rata minimal sekitar 85.00.'
                    ],
                    [
                        'question' => 'Apakah sertifikat kejuaraan online diakui?', 
                        'answer' => 'Sertifikat kejuaraan yang diakui adalah yang diselenggarakan secara berjenjang oleh instansi pemerintah (Kemdikbud/Kemenpora) atau induk organisasi resmi. Sertifikat webinar atau lomba online non-resmi biasanya memiliki bobot yang lebih kecil atau tidak dihitung.'
                    ],
                    [
                        'question' => 'Apakah ada biaya pendaftaran?', 
                        'answer' => 'Tidak ada biaya pendaftaran (gratis). Seluruh proses PPDB di SMA Negeri 1 Baleendah tidak dipungut biaya apapun.'
                    ]
                ]
            ]]
        );

        // Persyaratan Umum
        SpmbSetting::updateOrCreate(
            ['section_key' => 'persyaratan'],
            ['content' => [
                'items' => [
                    ['name' => 'Ijazah / Surat Keterangan Lulus', 'description' => 'Asli dan Fotokopi', 'required' => true],
                    ['name' => 'Akta Kelahiran', 'description' => 'Asli dan Fotokopi', 'required' => true],
                    ['name' => 'Kartu Keluarga', 'description' => 'Minimal diterbitkan 1 tahun sebelum pendaftaran', 'required' => true],
                    ['name' => 'Buku Rapor', 'description' => 'Semester 1 s.d 5', 'required' => true],
                ],
                'additional_notes' => 'Semua dokumen asli wajib dibawa saat verifikasi fisik / daftar ulang.'
            ]]
        );

        $this->command->info('SPMB data populated successfully.');
    }

    private function attachMedia($model, $collection, $filename)
    {
        $path = public_path("images/{$filename}");
        $smansaPath = base_path('foto-guru/SMANSA.jpeg');

        if (File::exists($path)) {
            $model->clearMediaCollection($collection);
            $model->addMedia($path)->preservingOriginal()->toMediaCollection($collection);
        } elseif (File::exists($smansaPath)) {
            $model->clearMediaCollection($collection);
            $model->addMedia($smansaPath)->preservingOriginal()->toMediaCollection($collection);
        }
    }
}
