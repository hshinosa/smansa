<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Teacher extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'type',
        'position',
        'department',
        'nip',
        'email',
        'phone',
        'bio',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['image_url'];

    /**
     * Get the teacher's profile photo URL.
     */
    public function getImageUrlAttribute()
    {
        return $this->getFirstMediaUrl('photos', 'webp') ?: '/images/keluarga-besar-sman1-baleendah.png';
    }

    /**
     * Register media conversions for teacher profile photos
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        // Portrait-style half-body cropping (3:4 aspect ratio)
        // Mobile size (300x400) - WebP
        $this
            ->addMediaConversion('mobile')
            ->width(300)
            ->height(400)
            ->fit(\Spatie\Image\Enums\Fit::Crop, 300, 400)
            ->format('webp')
            ->quality(80)
            ->performOnCollections('photos')
            ->nonQueued();

        // Tablet size (450x600) - WebP
        $this
            ->addMediaConversion('tablet')
            ->width(450)
            ->height(600)
            ->fit(\Spatie\Image\Enums\Fit::Crop, 450, 600)
            ->format('webp')
            ->quality(85)
            ->performOnCollections('photos')
            ->nonQueued();

        // Desktop size (600x800) - WebP
        $this
            ->addMediaConversion('desktop')
            ->width(600)
            ->height(800)
            ->fit(\Spatie\Image\Enums\Fit::Crop, 600, 800)
            ->format('webp')
            ->quality(90)
            ->performOnCollections('photos')
            ->nonQueued();

        // Large/HD size (900x1200) - WebP
        $this
            ->addMediaConversion('large')
            ->width(900)
            ->height(1200)
            ->fit(\Spatie\Image\Enums\Fit::Crop, 900, 1200)
            ->format('webp')
            ->quality(90)
            ->performOnCollections('photos')
            ->nonQueued();

        // Original as WebP with portrait crop
        $this
            ->addMediaConversion('webp')
            ->width(600)
            ->height(800)
            ->fit(\Spatie\Image\Enums\Fit::Crop, 600, 800)
            ->format('webp')
            ->quality(95)
            ->performOnCollections('photos')
            ->nonQueued();

        // Thumbnail for list view (square for admin, portrait for display)
        $this
            ->addMediaConversion('thumb')
            ->width(200)
            ->height(267)
            ->fit(\Spatie\Image\Enums\Fit::Crop, 200, 267)
            ->format('webp')
            ->quality(85)
            ->nonQueued();
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('photos')
            ->singleFile() // One profile photo per teacher
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
