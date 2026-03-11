<?php

namespace Database\Seeders;

use App\Models\Seragam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PopulateSeragamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seragams = [
            [
                'name' => 'Seragam Putih-Abu',
                'slug' => 'seragam-putih-abu',
                'category' => 'Seragam Harian',
                'description' => 'Seragam harian putih abu-abu untuk siswa SMAN 1 Baleendah. Dipakai setiap hari sekolah kecuali hari-hari khusus.',
                'usage_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'rules' => "1. Kemeja putih berkerah harus dimasukkan ke dalam celana/rok\n2. Celana/rok abu-abu polos tanpa motif\n3. Sepatu hitam dengan kaos kaki putih\n4. Helm tidak boleh berwarna neon atau flashy\n5. Identitas siswa (pin nama) wajib dikenakan di dada kiri",
                'sort_order' => 1,
                'is_active' => true,
                'image' => 'SeragamPutihAbu.jpg',
            ],
            [
                'name' => 'Seragam Batik',
                'slug' => 'seragam-batik',
                'category' => 'Seragam Khusus',
                'description' => 'Seragam batik khusus SMAN 1 Baleendah yang dipakai pada hari-hari tertentu seperti hari Senin pertama setiap bulan, acara resmi sekolah, dan kegiatan tertentu.',
                'usage_days' => ['monday'],
                'rules' => "1. Batik SMAN 1 Baleendah dengan logo школа\n2. Dipakai dengan celana hitam/rok hitam\n3. Sepatu hitam\n4. Kaos kaki putih\n5. Bersepatu hitam dan berdasar",
                'sort_order' => 2,
                'is_active' => true,
                'image' => 'SeragamBatik.jpg',
            ],
            [
                'name' => 'Seragam Olah Raga',
                'slug' => 'seragam-olah-raga',
                'category' => 'Seragam Olahraga',
                'description' => 'Seragam olahraga resmi SMAN 1 Baleendah untuk kegiatan pembelajaran olahraga dan aktivitas fisik.',
                'usage_days' => ['saturday'],
                'rules' => "1. Kaos olahraga hijau tua dengan logo sekolah\n2. Celana training hijau tua\n3. Sepatu olahraga (sneakers)\n4. Topi/windbreaker hijau (sesuai petunjuk guru olahraga)\n5. Bawa selalu handuk dan minum",
                'sort_order' => 3,
                'is_active' => true,
                'image' => 'SeragamOlahraga.jpg',
            ],
            [
                'name' => 'Seragam Batik Koko',
                'slug' => 'seragam-batik-koko',
                'category' => 'Seragam Khusus',
                'description' => 'Seragam batik koko untuk acara tertentu seperti hari besar agama, Maulid Nabi, dan kegiatan keagamaan sekolah.',
                'usage_days' => ['monday', 'friday'],
                'rules' => "1. Baju batik koko polos\n2. Celana hitam panjang\n3. Peci/helmut hitam (untuk laki-laki)\n4. Sepatu hitam\n5. Kaos kaki putih",
                'sort_order' => 4,
                'is_active' => true,
                'image' => 'SeragamKoko.jpg',
            ],
            [
                'name' => 'Seragam Sunda',
                'slug' => 'seragam-sunda',
                'category' => 'Seragam Ekstrakurikuler',
                'description' => 'Seragam tradisional Sunda untuk anggota ekstrakurikuler seni budaya Sunda seperti Degung, Tari, dan Paduan Suara.',
                'usage_days' => ['saturday'],
                'rules' => "1. Baju tradisional Sunda sesuai jenis ekstrakurikuler\n2. Dipakai saat latihan dan tampil\n3. Rapi dan sesuai ketentuan Pembina",
                'sort_order' => 5,
                'is_active' => true,
                'image' => 'SeragamSunda.jpg',
            ],
            [
                'name' => 'Jas Laboratorium',
                'slug' => 'jas-laboratorium',
                'category' => 'Seragam Khusus',
                'description' => 'Jas laboratorium wajib dipakai saat kegiatan praktikum di laboratorium IPA (Fisika, Kimia, Biologi).',
                'usage_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'rules' => "1. Jas laboratorium putih berkerah\n2. Dipakai saat masuk laboratorium\n3. Disimpan dengan rapi setelah praktikum\n4. Tidak boleh diperlebar atau dimodifikasi",
                'sort_order' => 6,
                'is_active' => true,
                'image' => 'Jas Lab.jpg',
            ],
            [
                'name' => 'Seragam Kotak-Kotak',
                'slug' => 'seragam-kotak-kotak',
                'category' => 'Seragam Harian',
                'description' => 'Seragam harian dengan motif kotak-kotak untuk variety hari Selasa.',
                'usage_days' => ['tuesday'],
                'rules' => "1. Kemeja kotak-kotak sesuai ketentuan sekolah\n2. Celana/rok hitam polos\n3. Sepatu hitam dengan kaos kaki putih\n4. Identitas siswa wajib dikenakan",
                'sort_order' => 7,
                'is_active' => true,
                'image' => 'SeragamKotakKotak.jpg',
            ],
        ];

        foreach ($seragams as $item) {
            $imageFile = $item['image'] ?? null;
            unset($item['image']);

            $seragam = Seragam::create($item);

            if ($imageFile) {
                $sourcePath = base_path("seragam/{$imageFile}");
                if (File::exists($sourcePath)) {
                    $seragam->addMedia($sourcePath)
                        ->preservingOriginal()
                        ->toMediaCollection('images');
                }
            }
        }

        $this->command->info('Seragam seeded successfully.');
    }
}
