<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LandingPageSetting extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'section_key',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    /**
     * Register media conversions
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('mobile')
            ->width(375)
            ->format('webp')
            ->quality(80)
            ->nonQueued();

        $this->addMediaConversion('tablet')
            ->width(768)
            ->format('webp')
            ->quality(85)
            ->nonQueued();

        $this->addMediaConversion('desktop')
            ->width(1280)
            ->format('webp')
            ->quality(90)
            ->nonQueued();

        $this->addMediaConversion('large')
            ->width(1920)
            ->format('webp')
            ->quality(90)
            ->nonQueued();

        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(95)
            ->nonQueued();
    }

    // Definisikan field yang valid untuk setiap section di sini juga
    public static function getSectionFields(): array
    {
        return [
            'hero' => [
                'title_line1', 
                'title_line2', 
                'hero_text', 
                'background_image_url',
                'student_image_url',
                'stats'
            ],
            'about_lp' => ['title', 'description_html', 'image_url'],
            'kepsek_welcome_lp' => ['title', 'kepsek_name', 'kepsek_title', 'kepsek_image_url', 'welcome_text_html'],
            'programs_lp' => ['title', 'description', 'items'], // 'items' array of objects
            'gallery_lp' => ['title', 'description', 'images'], // 'images' array of strings (urls)
            'cta_lp' => ['title', 'description'],
            'kalender_akademik' => ['title', 'description', 'calendar_image_url'],
            // Add other sections if available
        ];
    }

    public static function getDefaults(string $sectionKey): array
    {
        $defaults = [
            'hero' => [
                'title_line1' => 'Selamat Datang di',
                'title_line2' => 'SMA Negeri 1 Baleendah',
                'hero_text' => 'Sekolah penggerak prestasi dan inovasi masa depan. Kami berkomitmen mencetak lulusan yang cerdas, berakhlak mulia, dan siap bersaing di era global.',
                'background_image_url' => '/images/hero-bg-sman1baleendah.jpeg',
                'student_image_url' => '/images/anak-sma.png',
                'stats' => [
                    ['label' => 'Akreditasi', 'value' => 'A (Unggul)', 'icon_name' => 'Trophy'],
                    ['label' => 'Lulusan ke PTN', 'value' => '90% Diterima', 'icon_name' => 'GraduationCap'],
                    ['label' => 'Siswa Aktif', 'value' => '1200+ Siswa', 'icon_name' => 'Users'],
                ]
            ],
            'about_lp' => [
                'title' => 'Tentang SMAN 1 Baleendah',
                'description_html' => '<p>SMA Negeri 1 Baleendah adalah salah satu sekolah menengah atas negeri unggulan di Kabupaten Bandung yang memiliki reputasi baik dalam pendidikan akademik.</p><p>Kami menawarkan tiga program studi unggulan: MIPA (Matematika dan Ilmu Pengetahuan Alam), IPS (Ilmu Pengetahuan Sosial), dan Bahasa (Ilmu Bahasa dan Budaya) yang didukung oleh pengajar kompeten dan fasilitas memadai untuk mencetak lulusan berprestasi dan berkarakter.</p>',
                'image_url' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
            'kepsek_welcome_lp' => [
                'title' => 'Sambutan Kepala Sekolah',
                'kepsek_name' => 'Drs. Ahmad Suryadi, M.Pd.',
                'kepsek_title' => 'Kepala SMA Negeri 1 Baleendah',
                'kepsek_image_url' => '/images/kepala-sekolah.jpg', // Ensure path is correct
                'welcome_text_html' => '<p><strong>Sampurasun!</strong></p><p>Puji syukur senantiasa kita panjatkan kehadirat Allah SWT, Tuhan Yang Maha Esa, atas limpahan rahmat dan karunia-Nya. Saya menyambut Anda di situs resmi SMA Negeri 1 Baleendah, jendela informasi utama sekolah kami.</p><p>Kami berkomitmen untuk mengembangkan potensi peserta didik secara optimal melalui program MIPA, IPS, dan Bahasa serta membentuk karakter mulia. Mari bersama membangun masa depan yang gemilang.</p><p>Wassalamu’alaikum Warahmatullahi Wabarakatuh.</p>',
            ],
            'programs_lp' => [
                'title' => 'Program Studi',
                'description' => 'Pilihan program studi yang dirancang untuk mempersiapkan siswa menuju jenjang pendidikan tinggi dan karir masa depan.',
                'items' => [
                    [
                        'title' => 'MIPA',
                        'fullName' => 'Matematika & Ilmu Pengetahuan Alam',
                        'icon_name' => 'Microscope',
                        'description' => 'Program unggulan bagi siswa yang berminat dalam sains, teknologi, dan matematika. Fasilitas laboratorium lengkap.',
                        'link' => '/akademik/program-studi/mipa'
                    ],
                    [
                        'title' => 'IPS',
                        'fullName' => 'Ilmu Pengetahuan Sosial',
                        'icon_name' => 'Globe',
                        'description' => 'Mendalami fenomena sosial, ekonomi, dan sejarah. Membentuk karakter kritis dan berwawasan luas.',
                        'link' => '/akademik/program-studi/ips'
                    ],
                    [
                        'title' => 'Bahasa',
                        'fullName' => 'Ilmu Bahasa',
                        'icon_name' => 'BookOpen',
                        'description' => 'Eksplorasi bahasa asing dan seni budaya. Mempersiapkan siswa kompeten dalam komunikasi global.',
                        'link' => '/akademik/program-studi/bahasa'
                    ]
                ]
            ],
            'gallery_lp' => [
                'title' => 'Galeri Sekolah',
                'description' => 'Momen-momen seru dan kegiatan inspiratif siswa-siswi SMAN 1 Baleendah.',
                'images' => [
                    '/images/panen-karya-sman1-baleendah.jpg',
                    '/images/hero-bg-sman1baleendah.jpeg',
                    '/images/keluarga-besar-sman1-baleendah.png',
                    '/images/hero-bg-sman1baleendah.jpeg'
                ]
            ],
            'cta_lp' => [
                'title' => 'Siap Menjadi Bagian dari Keluarga Besar SMAN 1 Baleendah?',
                'description' => 'Dapatkan informasi lengkap mengenai pendaftaran peserta didik baru, jadwal, dan persyaratan yang dibutuhkan.',
            ],
            'kalender_akademik' => [
                'title' => 'Kalender Akademik SMA Negeri 1 Baleendah',
                'description' => 'Kalender akademik resmi yang ditetapkan oleh pemerintah untuk tahun ajaran ini.',
            ],
            // Add defaults for other sections
        ];

        return $defaults[$sectionKey] ?? [];
    }

    /**
     * Helper untuk mengambil konten section, menggabungkan dengan default
     * jika data dari DB tidak lengkap atau tidak ada.
     */
    public static function getContent(string $sectionKey, ?array $dbContent): array
    {
        $defaults = self::getDefaults($sectionKey);
        if ($dbContent === null) {
            return $defaults;
        }

        // Gabungkan default dengan data dari DB, data DB akan menimpa default jika ada
        // Ini memastikan semua field default ada di hasil akhir.
        return array_merge($defaults, $dbContent);
    }
}
