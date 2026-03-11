<?php

namespace Database\Seeders;

use App\Models\PtnUniversity;
use Illuminate\Database\Seeder;

class PtnUniversitySeeder extends Seeder
{
    public function run(): void
    {
        $universities = [
            ['name' => 'Institut Teknologi Bandung', 'short_name' => 'ITB', 'color' => '#059669'],
            ['name' => 'Universitas Padjadjaran', 'short_name' => 'UNPAD', 'color' => '#1E40AF'],
            ['name' => 'Universitas Pendidikan Indonesia', 'short_name' => 'UPI', 'color' => '#D97706'],
            ['name' => 'Institut Pertanian Bogor', 'short_name' => 'IPB', 'color' => '#7C3AED'],
            ['name' => 'Politeknik Negeri Bandung', 'short_name' => 'POLBAN', 'color' => '#0891B2'],
            ['name' => 'UIN Sunan Gunung Djati', 'short_name' => 'UIN-SGD', 'color' => '#DC2626'],
            ['name' => 'Universitas Indonesia', 'short_name' => 'UI', 'color' => '#F59E0B'],
            ['name' => 'Universitas Gadjah Mada', 'short_name' => 'UGM', 'color' => '#374151'],
            ['name' => 'Universitas Brawijaya', 'short_name' => 'UB', 'color' => '#1D4ED8'],
            ['name' => 'Institut Seni Budaya Indonesia', 'short_name' => 'ISBI', 'color' => '#EC4899'],
            ['name' => 'Universitas Sriwijaya', 'short_name' => 'UNSRI', 'color' => '#F472B6'],
            ['name' => 'Universitas Andalas', 'short_name' => 'UNAND', 'color' => '#10B981'],
            ['name' => 'Universitas Negeri Semarang', 'short_name' => 'UNNES', 'color' => '#FBBF24'],
            ['name' => 'Politeknik Kesehatan Bandung', 'short_name' => 'POLTEKKES BDG', 'color' => '#06B6D4'],
        ];

        foreach ($universities as $uni) {
            PtnUniversity::updateOrCreate(
                ['name' => $uni['name']],
                $uni
            );
        }
    }
}
