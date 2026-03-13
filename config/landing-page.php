<?php

/**
 * Landing Page Configuration
 *
 * Default content for landing page sections.
 * These values are used as fallbacks when database settings are not configured.
 *
 * Usage:
 *   config('landing-page.hero.title_line1')
 *   config('landing-page.about.title')
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Hero Section
    |--------------------------------------------------------------------------
    |
    | Main hero section content displayed at the top of the landing page.
    |
    */
    'hero' => [
        'title_line1' => 'Selamat Datang di',
        'title_line2' => 'SMA Negeri 1 Baleendah',
        'hero_text' => 'Sekolah penggerak prestasi dan inovasi masa depan. Kami berkomitmen mencetak lulusan yang cerdas, berakhlak mulia, dan siap bersaing di era global.',
        'background_image_url' => '/images/hero-bg-sman1baleendah.jpeg',
        'student_image_url' => '/images/anak-sma.png',
        'stats' => [
            ['label' => 'Akreditasi', 'value' => 'A', 'icon' => 'Trophy'],
            ['label' => 'Lulusan', 'value' => '15k+', 'icon' => 'GraduationCap'],
            ['label' => 'Siswa Aktif', 'value' => '1.2k+', 'icon' => 'Users'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | About Section
    |--------------------------------------------------------------------------
    |
    | School description and background information.
    |
    */
    'about' => [
        'title' => 'Tentang Kami',
        'description_html' => '<p>SMAN 1 Baleendah berdiri sejak tahun 1975 dan telah menjadi salah satu sekolah rujukan di Jawa Barat. Dengan visi menjadi sekolah unggul dalam prestasi dan berwawasan lingkungan, kami terus berinovasi dalam pembelajaran berbasis teknologi dan penguatan karakter.</p><p>Kami percaya bahwa setiap siswa memiliki potensi unik yang perlu dikembangkan melalui bimbingan yang tepat dan fasilitas yang memadai.</p>',
        'image_url' => '/images/hero-bg-sman1baleendah.jpeg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kepsek Welcome Section
    |--------------------------------------------------------------------------
    |
    | Principal's welcome message section.
    |
    */
    'kepsek' => [
        'title' => 'Sambutan Kepala Sekolah',
        'kepsek_name' => 'Drs. H. Ahmad Suryadi, M.Pd.',
        'kepsek_title' => 'Kepala SMA Negeri 1 Baleendah',
        'kepsek_image_url' => '/images/hero-bg-sman1baleendah.jpeg',
        'welcome_text_html' => '<p>Assalamu\'alaikum Warahmatullahi Wabarakatuh...</p><p>Saya mewakili seluruh warga SMA Negeri 1 Baleendah menyampaikan terima kasih atas kunjungan Anda ke website resmi kami...</p><p>Hormat kami,</p>',
    ],

    /*
    |--------------------------------------------------------------------------
    | Programs Section
    |--------------------------------------------------------------------------
    |
    | Academic programs showcase section header.
    |
    */
    'programs' => [
        'title' => 'Program Akademik',
        'description' => 'Berbagai program inovatif yang dirancang untuk mengembangkan potensi siswa secara holistik.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gallery Section
    |--------------------------------------------------------------------------
    |
    | Gallery section header content.
    |
    */
    'gallery' => [
        'title' => 'Galeri Sekolah',
        'description' => 'Momen-momen seru dan kegiatan inspiratif siswa-siswi SMAN 1 Baleendah.',
    ],

    /*
    |--------------------------------------------------------------------------
    | CTA Section
    |--------------------------------------------------------------------------
    |
    | Call-to-action section content.
    |
    */
    'cta' => [
        'title' => 'Siap Menjadi Bagian dari Keluarga Besar SMAN 1 Baleendah?',
        'description' => 'Dapatkan informasi lengkap mengenai pendaftaran peserta didik baru, jadwal, dan persyaratan yang dibutuhkan.',
        'button_text' => 'Daftar Sekarang',
        'button_link' => '/informasi-spmb',
    ],
];
