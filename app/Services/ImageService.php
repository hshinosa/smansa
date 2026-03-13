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
     * Transform a single post with media for listing views
     * Tries collections in order and falls back to database field
     *
     * @param mixed $post
     * @param array $collections Ordered list of collection names to try
     * @return array
     */
    public function transformPostWithMedia($post, array $collections = ['featured', 'gallery']): array
    {
        $data = $post->toArray();
        $media = null;

        // Try each collection in order
        foreach ($collections as $collection) {
            $media = $this->getFirstMediaData($post, $collection);
            if ($media) {
                break;
            }
        }

        if ($media) {
            $data['image'] = $media;
            $data['featured_image'] = $media['original_url'];
        } else {
            // Final fallback to database field - only if it's a full URL
            $data['featured_image'] = $post->featured_image
                ? (str_starts_with($post->featured_image, 'http') ? $post->featured_image : null)
                : null;
        }

        return $data;
    }

    /**
     * Transform a single post with media for detail views (includes gallery)
     *
     * @param mixed $post
     * @param array $collections Ordered list of collection names to try for featured image
     * @return array
     */
    public function transformPostDetailWithMedia($post, array $collections = ['featured', 'gallery']): array
    {
        $data = $this->transformPostWithMedia($post, $collections);

        // For detail view, also include gallery images if they exist
        $galleryMedia = $this->getAllMediaData($post, 'gallery');
        if (!empty($galleryMedia)) {
            $data['galleryImages'] = $galleryMedia;
        }

        // Rename 'image' to 'featuredImage' for detail view consistency
        if (isset($data['image'])) {
            $data['featuredImage'] = $data['image'];
            unset($data['image']);
        }

        return $data;
    }

    /**
     * Transform a collection of posts with media
     *
     * @param Collection $posts
     * @param array $collections Ordered list of collection names to try
     * @return Collection
     */
    public function transformPostsCollection(Collection $posts, array $collections = ['featured', 'gallery']): Collection
    {
        return $posts->map(function ($post) use ($collections) {
            return $this->transformPostWithMedia($post, $collections);
        });
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
