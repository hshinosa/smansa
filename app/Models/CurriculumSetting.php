<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CurriculumSetting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['section_key', 'content'];

    protected $casts = [
        'content' => 'array',
    ];

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('mobile')
            ->width(375)
            ->format('webp')
            ->quality(80)
            ->nonQueued();

        $this->addMediaConversion('desktop')
            ->width(1280)
            ->format('webp')
            ->quality(90)
            ->nonQueued();

        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(90)
            ->nonQueued();
    }

    public static function getSectionMeta(): array
    {
        return [
            'hero' => ['label' => 'Hero', 'description' => 'Judul dan subjudul utama.', 'icon' => 'BookOpen', 'hasMedia' => true],
            'problem' => ['label' => 'Masalah & PISA 2022', 'description' => 'Fenomena Schooling But Not Learning dan statistik PISA.', 'icon' => 'Activity'],
            'definition' => ['label' => 'Definisi & 4 Olah', 'description' => 'Definisi pembelajaran mendalam dan 4 olah.', 'icon' => 'BookOpen'],
            'principles' => ['label' => 'Prinsip 3M', 'description' => 'Berkesadaran, bermakna, menggembirakan.', 'icon' => 'ListChecks'],
            'learning_cycle' => ['label' => 'Siklus Belajar', 'description' => 'Memahami, mengaplikasi, merefleksi.', 'icon' => 'Repeat'],
            'design_framework' => ['label' => 'Kerangka Implementasi', 'description' => 'Praktik, kemitraan, lingkungan, digital.', 'icon' => 'Network'],
            'competency_dimensions' => ['label' => 'Dimensi Kompetensi', 'description' => 'Dimensi kompetensi utama.', 'icon' => 'Users'],
            'learner_profile' => ['label' => 'Profil Pelajar Pancasila', 'description' => 'Output profil pelajar.', 'icon' => 'Users'],
            'infographic_deep_learning' => ['label' => 'Infografis Pembelajaran Mendalam', 'description' => 'Gambar pendukung utama.', 'icon' => 'ImageIcon', 'hasMedia' => true],
            'infographic_education_2045' => ['label' => 'Infografis Pendidikan Bermutu 2045', 'description' => 'Gambar pendukung PISA 2022.', 'icon' => 'ImageIcon', 'hasMedia' => true],
        ];
    }

    public static function getSectionFields(): array
    {
        return self::getSeedContent();
    }

    public static function getDefaults(string $sectionKey): array
    {
        $defaults = self::getSeedContent();

        return $defaults[$sectionKey] ?? [];
    }

    public static function getMediaCollections(): array
    {
        return [
            'hero' => 'hero_bg',
            'infographic_deep_learning' => 'infographic_deep_learning_image',
            'infographic_education_2045' => 'infographic_education_2045_image',
        ];
    }

    public static function getContent($key, $dbContent = null): array
    {
        $default = self::getDefaults($key);

        if (!$dbContent) {
            return $default;
        }

        return array_merge($default, $dbContent);
    }

    private static function getSeedContent(): array
    {
        return [
            'hero' => [
                'title' => 'Pembelajaran Mendalam',
                'subtitle' => 'Solusi Menuju Pendidikan Bermutu 2045',
            ],
            'problem' => [
                'title' => 'Mengapa Membutuhkannya?',
                'description' => 'Fenomena "Schooling But Not Learning" menunjukkan rendahnya keterampilan berpikir tingkat tinggi berdasarkan hasil PISA 2022.',
                'stats' => [
                    ['label' => 'Membaca', 'lots' => '99,2% (Level 1-3)', 'hots' => '0,8% (Level 4-6)'],
                    ['label' => 'Matematika', 'lots' => '99,6% (Level 1-3)', 'hots' => '0,4% (Level 4-6)'],
                    ['label' => 'Sains', 'lots' => '99,1% (Level 1-3)', 'hots' => '0,9% (Level 4-6)'],
                ],
            ],
            'definition' => [
                'title' => 'Apa itu Pembelajaran Mendalam?',
                'description' => 'Pendekatan holistik dan terpadu untuk menciptakan pengalaman belajar yang berkesadaran, bermakna, dan menggembirakan.',
                'items' => [
                    ['title' => 'Pikir', 'description' => 'Kognitif / Intelektual'],
                    ['title' => 'Hati', 'description' => 'Afektif / Emosional'],
                    ['title' => 'Rasa', 'description' => 'Estetika / Indera'],
                    ['title' => 'Raga', 'description' => 'Fisik / Kinestetik'],
                ],
            ],
            'principles' => [
                'title' => 'Tiga Prinsip Pembelajaran (3M)',
                'items' => [
                    ['title' => 'Berkesadaran (Mindful)', 'description' => 'Siswa sadar tujuan belajar, fokus, dan memiliki regulasi diri.'],
                    ['title' => 'Bermakna (Meaningful)', 'description' => 'Mengaitkan pengetahuan dengan pengalaman serta isu dunia nyata.'],
                    ['title' => 'Menggembirakan (Joyful)', 'description' => 'Suasana positif yang aman secara psikologis dan menantang secara intelektual.'],
                ],
            ],
            'learning_cycle' => [
                'title' => 'Tahapan Pengalaman Belajar',
                'steps' => [
                    ['title' => 'Memahami (Understand)', 'description' => 'Konstruksi pengetahuan dasar dan nilai esensial.'],
                    ['title' => 'Mengaplikasi (Apply)', 'description' => 'Penerapan kontekstual pada situasi baru.'],
                    ['title' => 'Merefleksi (Reflect)', 'description' => 'Evaluasi diri, metakognisi, dan pemaknaan nilai.'],
                ],
            ],
            'design_framework' => [
                'title' => 'Kerangka Implementasi Pembelajaran',
                'items' => [
                    ['title' => 'Praktik Pedagogis', 'description' => 'Fasilitasi aktif, bukan ceramah satu arah.'],
                    ['title' => 'Kemitraan Pembelajaran', 'description' => 'Hubungan setara antara guru, siswa, dan orang tua.'],
                    ['title' => 'Lingkungan Pembelajaran', 'description' => 'Iklim kelas yang inklusif secara fisik dan psikologis.'],
                    ['title' => 'Pemanfaatan Digital', 'description' => 'Teknologi sebagai akselerator inovasi.'],
                ],
            ],
            'competency_dimensions' => [
                'title' => 'Dimensi Kompetensi',
                'items' => [
                    'Keimanan dan Ketakwaan terhadap Tuhan YME',
                    'Kewargaan',
                    'Kreativitas',
                    'Kemandirian',
                    'Komunikasi',
                    'Kesehatan',
                    'Kolaborasi',
                    'Penalaran Kritis',
                ],
            ],
            'learner_profile' => [
                'title' => 'Profil Pelajar Pancasila',
                'items' => [
                    'Iman & Taqwa',
                    'Kewargaan',
                    'Nalar Kritis',
                    'Kreativitas',
                    'Kolaborasi',
                    'Mandiri',
                    'Kesehatan',
                    'Komunikasi',
                ],
            ],
            'infographic_deep_learning' => [
                'title' => 'Infografis Pembelajaran Mendalam',
                'description' => 'Memuat dimensi kompetensi, prinsip pembelajaran, dan kerangka implementasi pembelajaran mendalam.',
                'source' => 'Four Elements of Learning Design - New Pedagogies for Deep Learning',
            ],
            'infographic_education_2045' => [
                'title' => 'Infografis Pendidikan Bermutu 2045',
                'description' => 'Pembelajaran mendalam sebagai solusi menuju pendidikan bermutu 2045 dengan konteks PISA 2022.',
                'source' => 'PISA 2022',
            ],
        ];
    }
}
