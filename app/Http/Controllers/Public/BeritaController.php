<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Controller for public news/berita pages
 * Refactored from routes/web.php closure
 */
class BeritaController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display the news listing page
     */
    public function index()
    {
        $transformPosts = function ($posts) {
            return $posts->map(function ($post) {
                $data = $post->toArray();
                
                // Try to get media from 'featured' collection first
                $media = $this->imageService->getFirstMediaData($post, 'featured');
                
                // Fallback to 'gallery' collection if 'featured' is empty
                if (!$media) {
                    $media = $this->imageService->getFirstMediaData($post, 'gallery');
                }
                
                if ($media) {
                    $data['image'] = $media;
                    $data['featured_image'] = $media['original_url'];
                } else {
                    // Final fallback to database field - but ensure it's a full URL or null
                    $data['featured_image'] = $post->featured_image ? (str_starts_with($post->featured_image, 'http') ? $post->featured_image : null) : null;
                }
                
                return $data;
            });
        };

        $posts = Post::with(['author', 'media'])
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->get();
        $posts = $transformPosts($posts);

        $popularPosts = Post::with(['author', 'media'])
            ->where('status', 'published')
            ->orderBy('views_count', 'desc')
            ->take(5)
            ->get();
        $popularPosts = $transformPosts($popularPosts);

        return Inertia::render('BeritaPengumumanPage', [
            'posts' => $posts,
            'popularPosts' => $popularPosts
        ]);
    }

    /**
     * Display a single news article
     */
    public function show(string $slug)
    {
        $post = Post::with(['author', 'media'])->where('slug', $slug)->firstOrFail();
        $post->increment('views_count');
        
        // Transform single post to include media
        $postData = $post->toArray();
        
        // Try featured collection first, fallback to gallery
        $media = $this->imageService->getFirstMediaData($post, 'featured');
        if (!$media) {
            $media = $this->imageService->getFirstMediaData($post, 'gallery');
        }
        
        if ($media) {
            $postData['featuredImage'] = $media;
            $postData['featured_image'] = $media['original_url'];
        } else {
            // Final fallback to database field - but ensure it's a full URL or null
            $postData['featured_image'] = $post->featured_image ? (str_starts_with($post->featured_image, 'http') ? $post->featured_image : null) : null;
        }
        
        // Get gallery images if exists (for carousel)
        $galleryMedia = $this->imageService->getAllMediaData($post, 'gallery');
        if (!empty($galleryMedia)) {
            $postData['galleryImages'] = $galleryMedia;
        }
        
        $relatedPosts = Post::where('id', '!=', $post->id)
            ->where('category', $post->category)
            ->where('status', 'published')
            ->latest('published_at')
            ->take(3)
            ->with(['author', 'media'])
            ->get()
            ->map(function ($p) {
                $d = $p->toArray();
                
                // Try featured collection first, fallback to gallery
                $m = $this->imageService->getFirstMediaData($p, 'featured');
                if (!$m) {
                    $m = $this->imageService->getFirstMediaData($p, 'gallery');
                }
                
                if ($m) {
                    $d['featuredImage'] = $m;
                    $d['featured_image'] = $m['original_url'];
                } else {
                    $d['featured_image'] = $p->featured_image ? (str_starts_with($p->featured_image, 'http') ? $p->featured_image : null) : null;
                }
                
                return $d;
            });

        return Inertia::render('BeritaDetailPage', [
            'post' => $postData,
            'relatedPosts' => $relatedPosts
        ]);
    }
}
