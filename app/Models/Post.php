<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'category',
        'status',
        'author_id',
        'published_at',
        'is_featured',
        'views_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    public function author()
    {
        return $this->belongsTo(Admin::class, 'author_id');
    }

    /**
     * Register media conversions for responsive images and WebP
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('mobile')
            ->width(375)
            ->format('webp')
            ->quality(75)
            ->performOnCollections('featured')
            ->nonQueued();

        $this
            ->addMediaConversion('tablet')
            ->width(768)
            ->format('webp')
            ->quality(75)
            ->performOnCollections('featured')
            ->nonQueued();

        $this
            ->addMediaConversion('desktop')
            ->width(1280)
            ->format('webp')
            ->quality(70)
            ->performOnCollections('featured')
            ->nonQueued();

        $this
            ->addMediaConversion('large')
            ->width(1920)
            ->format('webp')
            ->quality(65)
            ->performOnCollections('featured')
            ->nonQueued();

        $this
            ->addMediaConversion('webp')
            ->format('webp')
            ->quality(70)
            ->performOnCollections('featured')
            ->nonQueued();

        $this
            ->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->format('webp')
            ->quality(65)
            ->nonQueued();
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('featured')
            ->singleFile() // Only one featured image
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    // ============================================
    // Full-Text Search Scopes (Phase 2 Performance)
    // ============================================

    /**
     * Full-text search scope for PostgreSQL
     * Uses Indonesian language configuration
     */
    public function scopeSearchFullText($query, string $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->whereRaw("
            to_tsvector('indonesian', COALESCE(title, '') || ' ' || COALESCE(excerpt, '') || ' ' || COALESCE(content, ''))
            @@ plainto_tsquery('indonesian', ?)
        ", [$searchTerm]);
    }

    /**
     * Search with ranking (most relevant first)
     */
    public function scopeSearchWithRanking($query, string $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->whereRaw("
            to_tsvector('indonesian', COALESCE(title, '') || ' ' || COALESCE(excerpt, '') || ' ' || COALESCE(content, ''))
            @@ plainto_tsquery('indonesian', ?)
        ", [$searchTerm])
            ->orderByRaw("
            ts_rank(to_tsvector('indonesian', COALESCE(title, '') || ' ' || COALESCE(excerpt, '') || ' ' || COALESCE(content, '')),
            plainto_tsquery('indonesian', ?)) DESC
        ", [$searchTerm]);
    }

    /**
     * Fallback search using LIKE (for databases without full-text support)
     */
    public function scopeSearchFallback($query, string $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        $search = '%'.strtolower($searchTerm).'%';

        return $query->where(function ($q) use ($search) {
            $q->whereRaw('LOWER(title) LIKE ?', [$search])
                ->orWhereRaw('LOWER(excerpt) LIKE ?', [$search])
                ->orWhereRaw('LOWER(content) LIKE ?', [$search]);
        });
    }

    /**
     * Smart search that uses full-text if available, falls back to LIKE
     */
    public function scopeSmartSearch($query, string $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        // Try full-text search first, fall back to LIKE
        try {
            return $query->searchWithRanking($searchTerm);
        } catch (\Exception $e) {
            return $query->searchFallback($searchTerm);
        }
    }
}
