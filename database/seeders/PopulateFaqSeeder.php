<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faq;

class PopulateFaqSeeder extends Seeder
{
    public function run()
    {
        // Bersihkan data lama
        Faq::truncate();

        $faqs = [
            [
                'question' => 'Dimana lokasi SMA Negeri 1 Baleendah?',
                'answer' => 'SMA Negeri 1 Baleendah berlokasi di Jl. R.A.A. Wiranata Kusumah No.30, Baleendah, Kec. Baleendah, Kabupaten Bandung, Jawa Barat 40375.',
                'category' => 'Umum',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'question' => 'Jam berapa jam operasional tata usaha sekolah?',
                'answer' => 'Layanan administrasi Tata Usaha (TU) buka setiap hari Senin - Jumat pukul 07.00 - 15.00 WIB. Istirahat pukul 12.00 - 13.00 WIB.',
                'category' => 'Administrasi',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'question' => 'Bagaimana prosedur legalisir ijazah?',
                'answer' => 'Alumni dapat datang langsung ke bagian Tata Usaha dengan membawa ijazah asli dan fotokopi yang akan dilegalisir. Proses biasanya memakan waktu 1-2 hari kerja.',
                'category' => 'Alumni',
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'question' => 'Apakah sekolah menyediakan fasilitas jemputan siswa?',
                'answer' => 'Saat ini sekolah tidak menyediakan fasilitas jemputan resmi. Siswa biasanya menggunakan kendaraan pribadi, angkutan umum, atau layanan ojek online.',
                'category' => 'Fasilitas',
                'sort_order' => 4,
                'is_published' => true,
            ],
            [
                'question' => 'Bagaimana cara mengajukan pindah sekolah (mutasi) ke SMAN 1 Baleendah?',
                'answer' => 'Mutasi masuk tergantung pada ketersediaan bangku kosong. Orang tua harus mengajukan surat permohonan ke sekolah, melampirkan rapor, dan mengikuti tes seleksi jika ada kuota.',
                'category' => 'Akademik',
                'sort_order' => 5,
                'is_published' => true,
            ],
            [
                'question' => 'Apakah ada beasiswa untuk siswa berprestasi?',
                'answer' => 'Ya, sekolah menyalurkan berbagai beasiswa seperti PIP (Program Indonesia Pintar) dari pemerintah dan beasiswa prestasi dari komite sekolah atau mitra kerjasama.',
                'category' => 'Beasiswa',
                'sort_order' => 6,
                'is_published' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }

        $this->command->info('FAQ Contact populated successfully.');
    }
}
