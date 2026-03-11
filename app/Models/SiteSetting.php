<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SiteSetting extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['section_key', 'content'];

    protected $casts = [
        'content' => 'array',
    ];

    /**
     * Register media conversions
     */
    public function registerMediaConversions(Media $media = null): void
    {
        // Responsive variants for hero/banner images
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

    public static function getSectionFields(): array
    {
        return [
            'general' => [
                'site_name',
                'site_logo',
                'site_favicon',
                'address',
                'phone',
                'whatsapp',
                'email',
                'google_maps_url',
                'google_maps_embed_url',
            ],
            'social_media' => [
                'instagram',
                'facebook',
                'youtube',
                'twitter',
                'linkedin',
            ],
            'footer' => [
                'copyright_text',
                'footer_description',
            ],
            'hero_teachers' => [
                'title',
                'subtitle',
                'image',
            ],
            'hero_posts' => [
                'title',
                'subtitle',
                'image',
            ],
            'hero_gallery' => [
                'title',
                'subtitle',
                'image',
            ],
            'hero_alumni' => [
                'title',
                'subtitle',
                'image',
            ],
            'hero_extracurricular' => [
                'title',
                'subtitle',
                'image',
            ],
            'hero_contact' => [
                'title',
                'subtitle',
                'image',
            ],
            'hero_program' => [
                'title',
                'subtitle',
                'image',
            ],
        ];
    }

    protected static function defaults(): array
    {
        return [
            'general' => [
                'site_name' => 'SMAN 1 Baleendah',
                'site_logo' => '/images/logo-sman1-baleendah.png',
                'site_favicon' => '/favicon.ico',
                'address' => 'Jl. R.A.A. Wiranatakoesoemah No.30, Baleendah, Kec. Baleendah, Kabupaten Bandung, Jawa Barat 40375',
                'phone' => '(022) 5940262',
                'whatsapp' => '+6281234567890', // Update dengan nomor WA sebenarnya
                'email' => 'info@sman1baleendah.sch.id',
                'google_maps_url' => 'https://maps.app.goo.gl/7ef8aaa0fcd59ec9',
                'google_maps_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.8947!2d107.6298!3d-6.9876!2m2!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e62c87267923%3A0x7ef8aaa0fcd59ec9!2sSMAN%201%20Baleendah%2C%20Baleendah%2C%20Kabupaten%20Bandung%2C%20Jawa%20Barat!5e0!3m2!1sid!2sid!4v1746550004423!5m2!1sid!2sid',
            ],
            'social_media' => [
                'instagram' => 'https://www.instagram.com/sman1baleendah',
                'facebook' => 'https://www.facebook.com/SMAN1Baleendah',
                'youtube' => 'https://www.youtube.com/@sman1baleendah',
                'twitter' => 'https://twitter.com/sman1baleendah',
                'linkedin' => 'https://www.linkedin.com/school/sman-1-baleendah/',
            ],
            'footer' => [
                'copyright_text' => '© 2025 SMAN 1 Baleendah. All rights reserved.',
                'footer_description' => 'SMA Negeri 1 Baleendah adalah sekolah penggerak prestasi dan inovasi masa depan yang berkomitmen mencetak lulusan cerdas dan berakhlak mulia.',
            ],
            'hero_teachers' => [
                'title' => 'Guru & Staff',
                'subtitle' => 'Tenaga pendidik dan kependidikan yang berdedikasi untuk kemajuan pendidikan.',
                'image' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
            'hero_posts' => [
                'title' => 'Berita & Pengumuman',
                'subtitle' => 'Informasi terbaru seputar kegiatan dan prestasi sekolah.',
                'image' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
            'hero_gallery' => [
                'title' => 'Galeri Kegiatan',
                'subtitle' => 'Momen-momen berharga dalam berbagai aktivitas sekolah.',
                'image' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
            'hero_alumni' => [
                'title' => 'Jejak Alumni',
                'subtitle' => 'Kisah sukses dan kontribusi lulusan kami di berbagai bidang.',
                'image' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
            'hero_extracurricular' => [
                'title' => 'Ekstrakurikuler',
                'subtitle' => 'Wadah pengembangan bakat, minat, dan kreativitas siswa.',
                'image' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
            'hero_contact' => [
                'title' => 'Hubungi Kami',
                'subtitle' => 'Kami siap melayani informasi dan komunikasi dengan Anda.',
                'image' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
            'hero_program' => [
                'title' => 'Program Unggulan',
                'subtitle' => 'Membangun karakter dan kompetensi siswa melalui berbagai inisiatif positif.',
                'image' => '/images/hero-bg-sman1baleendah.jpeg',
            ],
        ];
    }

    public static function getDefaults(string $key): array
    {
        $defaults = self::defaults();

        return $defaults[$key] ?? [];
    }

    public static function getContent(string $key, mixed $dbContent = null): array
    {
        $default = self::getDefaults($key);

        if (!$dbContent) {
            return $default;
        }

        // Handle JSON string or array
        if (is_string($dbContent)) {
            $dbContent = json_decode($dbContent, true) ?? [];
        }

        return array_merge($default, (array) $dbContent);
    }

    /**
     * Get a specific field from general settings
     */
    public static function get(string $key): mixed
    {
        if (!in_array($key, self::getSectionFields()['general'])) {
            return null;
        }

        $setting = self::where('section_key', 'general')->first();
        
        if (!$setting) {
            $defaults = self::defaults();
            return $defaults['general'][$key] ?? null;
        }

        $content = $setting->content ?? [];
        return $content[$key] ?? null;
    }

    public static function getAllWithDefaults(): array
    {
        $settings = self::all()->keyBy('section_key');
        $siteSettings = [];

        // Get central hero image from general settings first
        $generalRow = $settings->get('general');
        $generalContent = $generalRow ? $generalRow->content : [];
        $centralHeroImage = $generalContent['hero_image'] ?? '/images/hero-bg-sman1baleendah.jpeg';

        foreach (array_keys(self::getSectionFields()) as $key) {
            $dbRow = $settings->get($key);
            $dbContent = ($dbRow) ? $dbRow->content : null;
            $siteSettings[$key] = self::getContent($key, $dbContent);
            
            // Override image field for all hero sections with central hero image
            if (str_starts_with($key, 'hero_') && isset($siteSettings[$key])) {
                $siteSettings[$key]['image'] = $centralHeroImage;
            }
        }

        return $siteSettings;
    }

    public static function getCachedAll(int $ttlSeconds = 900): array
    {
        return Cache::remember('site_settings_cached', $ttlSeconds, fn () => self::getAllWithDefaults());
    }

    public static function forgetCache(): void
    {
        Cache::forget('site_settings_cached');
    }
}
