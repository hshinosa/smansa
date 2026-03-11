<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProgramStudiSetting extends Model implements HasMedia
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'program_name',
        'section_key',
        'content',
        'thumbnail_card_url', // For landing page program cards
    ];

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

    public static function getSectionFields()
    {
        return [
            'hero' => [
                'title' => 'text',
                'description' => 'textarea',
                'background_image' => 'image',
            ],
            'core_subjects' => [
                'title' => 'text',
                'description' => 'textarea',
                'items' => 'list', // List of subjects
            ],
            'facilities' => [
                'title' => 'text',
                'description' => 'textarea',
                'main_image' => 'image',
                'main_title' => 'text',
                'main_description' => 'textarea',
                'items' => 'list', // List of other facilities
            ],
            'career_paths' => [
                'title' => 'text',
                'description' => 'textarea',
                'items' => 'list',
            ],
        ];
    }

    public static function getDefaults($sectionKey)
    {
        $defaults = [
            'hero' => [
                'title' => 'Program Studi',
                'description' => 'Deskripsi singkat program studi.',
                'background_image' => null,
            ],
            'core_subjects' => [
                'title' => 'Mata Pelajaran Unggulan',
                'description' => 'Kurikulum yang dirancang untuk masa depan.',
                'items' => [],
            ],
            'facilities' => [
                'title' => 'Fasilitas Riset & Praktikum',
                'description' => 'Fasilitas modern untuk mendukung pembelajaran.',
                'main_image' => null,
                'main_title' => 'Laboratorium Utama',
                'main_description' => 'Deskripsi laboratorium utama.',
                'items' => [],
            ],
            'career_paths' => [
                'title' => 'Prospek Karir',
                'description' => 'Peluang karir setelah lulus.',
                'items' => [],
            ],
        ];

        return $defaults[$sectionKey] ?? [];
    }

    public static function getContent($sectionKey, $dbContent = null)
    {
        $defaults = self::getDefaults($sectionKey);
        if (!$dbContent) {
            return $defaults;
        }
        return array_merge($defaults, $dbContent);
    }
}
