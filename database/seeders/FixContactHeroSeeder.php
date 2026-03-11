<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class FixContactHeroSeeder extends Seeder
{
    public function run()
    {
        // Fix Contact Hero
        $contactSetting = SiteSetting::updateOrCreate(
            ['section_key' => 'hero_contact'],
            ['content' => [
                'title' => 'Hubungi Kami',
                'subtitle' => 'Kami siap melayani dan menjawab pertanyaan Anda. Jangan ragu untuk menghubungi kami melalui saluran yang tersedia.',
                // image field is handled by media library usually, but text fallback is good
            ]]
        );

        // Fix Alumni Hero
        SiteSetting::updateOrCreate(
            ['section_key' => 'hero_alumni'],
            ['content' => [
                'title' => 'Alumni SMANSA',
                'subtitle' => 'Jejak langkah para alumni yang telah berkarya dan berprestasi di berbagai bidang.',
            ]]
        );

        // Fix News Hero
        SiteSetting::updateOrCreate(
            ['section_key' => 'hero_posts'],
            ['content' => [
                'title' => 'Berita & Pengumuman',
                'subtitle' => 'Informasi terbaru seputar kegiatan akademik, non-akademik, dan prestasi siswa.',
            ]]
        );

        $this->command->info('Hero settings fixed successfully.');
    }
}
