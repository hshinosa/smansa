<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Gallery extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',
        'type',
        'url',
        'thumbnail_url',
        'is_external',
        'category',
        'tags',
        'date',
        'is_featured',
    ];

    protected $casts = [
        'tags' => 'array',
        'date' => 'date',
    ];

    /**
     * Register media conversions for gallery images
     */
    public function registerMediaConversions(Media $media = null): void
    {
        // Mobile size (375px)
        $this
            ->addMediaConversion('mobile')
            ->width(375)
            ->format('webp')
            ->quality(80)
            ->performOnCollections('images')
            ->nonQueued();

        // Tablet size (768px)
        $this
            ->addMediaConversion('tablet')
            ->width(768)
            ->format('webp')
            ->quality(85)
            ->performOnCollections('images')
            ->nonQueued();

        // Desktop size (1280px)
        $this
            ->addMediaConversion('desktop')
            ->width(1280)
            ->format('webp')
            ->quality(90)
            ->performOnCollections('images')
            ->nonQueued();

        // Large/Full HD (1920px)
        $this
            ->addMediaConversion('large')
            ->width(1920)
            ->format('webp')
            ->quality(90)
            ->performOnCollections('images')
            ->nonQueued();

        // WebP original
        $this
            ->addMediaConversion('webp')
            ->format('webp')
            ->quality(95)
            ->performOnCollections('images')
            ->nonQueued();

        // Thumbnail for grid view
        $this
            ->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->format('webp')
            ->quality(80)
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
            ->addMediaCollection('videos')
            ->acceptsMimeTypes(['video/mp4', 'video/webm', 'video/quicktime']);
    }
}

