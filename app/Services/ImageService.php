<?php

namespace App\Services;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Collection;

class ImageService
{
    /**
     * Transform a single media object to responsive image data for React
     *
     * @param Media|null $media
     * @return array|null
     */
    public function getResponsiveImageData(?Media $media): ?array
    {
        if (!$media) {
            return null;
        }

        // Generate proxy URL to avoid 403 errors on PHP built-in server
        // when filenames contain special characters like parentheses
        $originalUrl = $this->getProxyUrl($media);

        return [
            'id' => $media->id,
            'original_url' => $originalUrl,
            'conversions' => [
                'mobile' => $media->hasGeneratedConversion('mobile') ? $this->getProxyUrl($media, 'mobile') : null,
                'tablet' => $media->hasGeneratedConversion('tablet') ? $this->getProxyUrl($media, 'tablet') : null,
                'desktop' => $media->hasGeneratedConversion('desktop') ? $this->getProxyUrl($media, 'desktop') : null,
                'large' => $media->hasGeneratedConversion('large') ? $this->getProxyUrl($media, 'large') : null,
                'webp' => $media->hasGeneratedConversion('webp') ? $this->getProxyUrl($media, 'webp') : null,
                'thumb' => $media->hasGeneratedConversion('thumb') ? $this->getProxyUrl($media, 'thumb') : null,
            ],
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'human_readable_size' => $media->human_readable_size,
            'custom_properties' => $media->custom_properties,
        ];
    }

    /**
     * Get proxy URL for media file to bypass PHP built-in server restrictions
     */
    public function getProxyUrl(Media $media, ?string $conversion = null): string
    {
        // Build path using custom path generator logic (md5 id)
        $pathGenerator = app(\App\Services\MediaLibrary\CustomPathGenerator::class);
        $basePath = $pathGenerator->getPath($media);

        if ($conversion) {
            // Conversions are always webp format
            $filePath = $basePath . 'conversions/' . $media->name . '-' . $conversion . '.webp';
        } else {
            $filePath = $basePath . $media->file_name;
        }

        return route('media.proxy', ['path' => $filePath], false);
    }

    /**
     * Get URL for media object (uses proxy to bypass PHP built-in server restrictions)
     */
    public function getMediaUrl(?Media $media, ?string $conversion = null): ?string
    {
        if (!$media) {
            return null;
        }

        return $this->getProxyUrl($media, $conversion);
    }

    /**
     * Get first media from collection with responsive data
     *
     * @param mixed $model
     * @param string $collection
     * @return array|null
     */
    public function getFirstMediaData($model, string $collection = 'default'): ?array
    {
        $media = $model->getFirstMedia($collection);
        return $this->getResponsiveImageData($media);
    }

    /**
     * Get all media from collection with responsive data
     *
     * @param mixed $model
     * @param string $collection
     * @return array
     */
    public function getAllMediaData($model, string $collection = 'default'): array
    {
        return $model->getMedia($collection)
            ->sortBy('order_column')
            ->map(function ($media) {
                return $this->getResponsiveImageData($media);
            })->toArray();
    }

    /**
     * Get all media from collection with responsive data, ordered
     *
     * @param mixed $model
     * @param string $collection
     * @return array
     */
    public function getOrderedMediaData($model, string $collection = 'default'): array
    {
        return $model->getMedia($collection)
            ->sortBy('order_column')
            ->map(function ($media) {
                return $this->getResponsiveImageData($media);
            })->toArray();
    }

    /**
     * Transform model with media to include responsive image data
     *
     * @param mixed $model
     * @param array $mediaCollections Array of collection names
     * @return array
     */
    public function transformModelWithMedia($model, array $mediaCollections = ['featured']): array
    {
        $data = $model->toArray();

        foreach ($mediaCollections as $collection) {
            if ($collection === 'gallery') {
                $data['gallery'] = $this->getAllMediaData($model, 'gallery');
            } else {
                $key = $collection === 'default' ? 'media' : $collection . 'Image';
                $data[$key] = $this->getFirstMediaData($model, $collection);
            }
        }

        return $data;
    }

    /**
     * Transform collection of models with media
     *
     * @param Collection $items
     * @param array $mediaCollections
     * @return array
     */
    public function transformCollectionWithMedia(Collection $items, array $mediaCollections = ['featured']): array
    {
        return $items->map(function ($item) use ($mediaCollections) {
            return $this->transformModelWithMedia($item, $mediaCollections);
        })->toArray();
    }

    /**
     * Get fallback image path
     *
     * @param string $type
     * @return string
     */
    public function getFallbackImage(string $type = 'default'): string
    {
        $fallbacks = [
            'hero' => '/images/hero-bg-sman1baleendah.jpeg',
            'post' => '/images/hero-bg-sman1baleendah.jpeg',
            'gallery' => '/images/hero-bg-sman1baleendah.jpeg',
            'default' => '/images/hero-bg-sman1baleendah.jpeg',
        ];

        return $fallbacks[$type] ?? $fallbacks['default'];
    }

    /**
     * Check if model has media in collection
     *
     * @param mixed $model
     * @param string $collection
     * @return bool
     */
    public function hasMedia($model, string $collection = 'default'): bool
    {
        return $model->getFirstMedia($collection) !== null;
    }
}
