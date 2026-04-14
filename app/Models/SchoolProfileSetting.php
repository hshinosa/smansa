<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SchoolProfileSetting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['section_key', 'content'];

    // Removed casts to avoid conflict with accessor/mutator
    
    /**
     * Get the content attribute and ensure it's always an array
     */
    public function getContentAttribute($value)
    {
        // If already array, return as is
        if (is_array($value)) {
            return $value;
        }
        
        // Decode JSON string to array
        if (is_string($value)) {
            // Remove wrapping quotes if exists
            $value = trim($value);
            if (substr($value, 0, 1) === '"' && substr($value, -1) === '"') {
                $value = substr($value, 1, -1);
            }
            
            // Unescape the JSON string
            $value = stripcslashes($value);
            
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        
        return [];
    }
    
    /**
     * Set the content attribute - encode array to JSON
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(Media $media = null): void
    {
        // Responsive variants for all images
        $this->addMediaConversion('mobile')
            ->width(375)
            ->format('webp')
            ->quality(75)
            ->nonQueued();

        $this->addMediaConversion('tablet')
            ->width(768)
            ->format('webp')
            ->quality(75)
            ->nonQueued();

        $this->addMediaConversion('desktop')
            ->width(1280)
            ->format('webp')
            ->quality(70)
            ->nonQueued();

        $this->addMediaConversion('large')
            ->width(1920)
            ->format('webp')
            ->quality(70)
            ->nonQueued();

        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(70)
            ->nonQueued();
    }

    public static function getSectionFields()
    {
        return [
            'hero_history' => [
                'title' => 'Judul Hero (Sejarah)',
                'image_url' => 'Background Hero (Sejarah)',
            ],
            'hero_vision_mission' => [
                'title' => 'Judul Hero (Visi Misi)',
                'image_url' => 'Background Hero (Visi Misi)',
            ],
            'hero_organization' => [
                'title' => 'Judul Hero (Organisasi)',
                'image_url' => 'Background Hero (Organisasi)',
            ],
            'history' => [
                'title' => 'Sejarah Sekolah',
                'description_html' => 'Deskripsi sejarah sekolah (HTML)',
                'image_url' => 'URL Gambar Sejarah',
                'timeline' => 'Timeline (JSON array of {year, title, description})',
            ],
            'vision_mission' => [
                'vision' => 'Visi Sekolah',
                'mission' => 'Misi Sekolah (JSON array of strings)',
            ],
            'facilities' => [
                'title' => 'Fasilitas Sekolah',
                'description' => 'Deskripsi singkat fasilitas',
                'items' => 'Daftar Fasilitas (JSON array of {title, description, image_url})',
            ],
            'organization' => [
                'title' => 'Struktur Organisasi',
                'image_url' => 'URL Gambar Bagan Struktur',
            ],
        ];
    }

    public static function getContent($key, $dbContent = null)
    {
        $defaults = [
            'hero_history' => [
                'title' => 'Profil & Sejarah',
                'image_url' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
            'hero_vision_mission' => [
                'title' => 'Visi & Misi',
                'image_url' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
            'hero_organization' => [
                'title' => 'Struktur Organisasi',
                'image_url' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
            'history' => [
                'title' => 'Sejarah Singkat',
                'description_html' => '<p>SMAN 1 Baleendah didirikan pada tahun 1975...</p>',
                'image_url' => '/images/hero-bg-sman1baleendah.jpeg',
                'timeline' => [
                    ['year' => '1975', 'title' => 'Pendirian', 'description' => 'Sekolah resmi didirikan.'],
                ],
            ],
            'vision_mission' => [
                'vision' => 'Terwujudnya Peserta Didik yang Beriman, Cerdas, Terampil, Mandiri, dan Berwawasan Global.',
                'mission' => [
                    'Melaksanakan pembelajaran berbasis teknologi dan inovasi.',
                    'Menanamkan nilai karakter dan budi pekerti luhur.',
                ],
            ],
            'facilities' => [
                'title' => 'Fasilitas Unggulan',
                'description' => 'Kami menyediakan fasilitas terbaik untuk mendukung proses belajar mengajar.',
                'items' => [
                    ['title' => 'Laboratorium Komputer', 'description' => 'Dilengkapi dengan perangkat modern.', 'icon' => 'Monitor'],
                ],
            ],
            'organization' => [
                'title' => 'Struktur Organisasi',
                'image_url' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
        ];

        $default = $defaults[$key] ?? [];
        
        if ($dbContent && is_array($dbContent)) {
            // For history section, ensure timeline from DB takes precedence
            if ($key === 'history' && isset($dbContent['timeline']) && is_array($dbContent['timeline'])) {
                $merged = array_merge($default, $dbContent);
                // Explicitly set timeline from DB to avoid overwrite
                $merged['timeline'] = $dbContent['timeline'];
                return $merged;
            }
            
            // For other sections with array fields, merge carefully
            return array_merge($default, $dbContent);
        }

        return $default;
    }
}
