<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Seragam extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'usage_days',
        'rules',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'usage_days' => 'array',
    ];

    protected $appends = ['image_url'];

    /**
     * Get the main image URL
     */
    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('images');
        return $media ? $media->getUrl() : null;
    }

    /**
     * Register media conversions for seragam images
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
            ->performOnCollections('images')
            ->nonQueued();

        // Mobile version
        $this
            ->addMediaConversion('mobile')
            ->width(640)
            ->height(480)
            ->format('webp')
            ->quality(75)
            ->performOnCollections('images')
            ->nonQueued();

        // Tablet version
        $this
            ->addMediaConversion('tablet')
            ->width(1024)
            ->height(768)
            ->format('webp')
            ->quality(80)
            ->performOnCollections('images')
            ->nonQueued();

        // Desktop version
        $this
            ->addMediaConversion('desktop')
            ->width(1920)
            ->height(1080)
            ->format('webp')
            ->quality(85)
            ->performOnCollections('images')
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
    }

    /**
     * Scope to order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
