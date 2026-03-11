<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Extracurricular extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'type', // 'organisasi' or 'ekstrakurikuler'
        'category',
        'description',
        'activity_description',
        'icon_name',
        'schedule',
        'coach_name',
        'achievements', // Legacy array field
        'achievements_data', // New structured JSON field
        'training_info', // JSON field for training details
        'bg_image',
        'profile_image',
        'sort_order',
        'is_active',
    ];

    /**
     * Available type constants
     */
    public const TYPE_ORGANISASI = 'organisasi';
    public const TYPE_EKSTRAKURIKULER = 'ekstrakurikuler';

    /**
     * Get all available types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_ORGANISASI => 'Organisasi',
            self::TYPE_EKSTRAKURIKULER => 'Ekstrakurikuler',
        ];
    }

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'achievements' => 'array', // Legacy field
        'achievements_data' => 'array', // New structured field
        'training_info' => 'array', // Training information
    ];

    protected $appends = ['image_url', 'bg_image_url', 'profile_image_url'];

    /**
     * Get the main image URL
     */
    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('images');
        return $media ? $media->getUrl() : null;
    }

    /**
     * Get the background image URL
     */
    public function getBgImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('bg_images');
        return $media ? $media->getUrl() : null;
    }

    /**
     * Get the profile image URL
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('profile_images');
        return $media ? $media->getUrl() : null;
    }

    /**
     * Register media conversions for extracurricular images
     */
    public function registerMediaConversions(Media $media = null): void
    {
        // Thumbnail for admin panel
        $this
            ->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->format('webp')
            ->quality(80)
            ->performOnCollections('images', 'bg_images', 'profile_images')
            ->nonQueued();

        // Mobile version
        $this
            ->addMediaConversion('mobile')
            ->width(640)
            ->height(480)
            ->format('webp')
            ->quality(75)
            ->performOnCollections('images', 'bg_images', 'profile_images')
            ->nonQueued();

        // Tablet version
        $this
            ->addMediaConversion('tablet')
            ->width(1024)
            ->height(768)
            ->format('webp')
            ->quality(80)
            ->performOnCollections('images', 'bg_images', 'profile_images')
            ->nonQueued();

        // Desktop version
        $this
            ->addMediaConversion('desktop')
            ->width(1920)
            ->height(1080)
            ->format('webp')
            ->quality(85)
            ->performOnCollections('images', 'bg_images', 'profile_images')
            ->nonQueued();
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        
        $this
            ->addMediaCollection('bg_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        
        $this
            ->addMediaCollection('profile_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
