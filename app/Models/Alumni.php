<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Alumni extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'graduation_year',
        'testimonial',
        'video_source',
        'content_type',
        'is_featured',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
        'graduation_year' => 'integer',
    ];

    protected $appends = ['image_url', 'video_url', 'video_thumbnail_url'];

    public function getImageUrlAttribute(): ?string
    {
        $firstGalleryImage = $this->getMedia('testimonial_images')
            ->sortBy('order_column')
            ->first();

        return $firstGalleryImage?->getUrl() ?: '/images/avatar-alumni-default.png';
    }

    public function getVideoUrlAttribute(): ?string
    {
        if ($this->content_type === 'video' && $this->video_source === 'upload') {
            return $this->getFirstMediaUrl('videos');
        }
        return $this->attributes['video_url'] ?? null;
    }

    public function getVideoThumbnailUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('video_thumbnails') ?: $this->image_url;
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->format('webp')
            ->quality(80)
            ->nonQueued();

        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(90)
            ->nonQueued();
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('testimonial_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this
            ->addMediaCollection('video_thumbnails')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this
            ->addMediaCollection('videos')
            ->singleFile()
            ->acceptsMimeTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime']);
    }

    /**
     * Helper method to extract YouTube video ID from URL
     */
    public static function extractYouTubeId($url)
    {
        if (empty($url)) {
            return null;
        }

        $patterns = [
            '/youtube\.com\/watch\?v=([^&]+)/',
            '/youtube\.com\/embed\/([^?]+)/',
            '/youtu\.be\/([^?]+)/',
            '/youtube\.com\/v\/([^?]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Check if video URL is a YouTube URL
     */
    public static function isYouTubeUrl($url)
    {
        return self::extractYouTubeId($url) !== null;
    }
}
