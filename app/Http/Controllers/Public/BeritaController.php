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
        $posts = Post::with(['author', 'media'])
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->get();
        $posts = $this->imageService->transformPostsCollection($posts);

        $popularPosts = Post::with(['author', 'media'])
            ->where('status', 'published')
            ->orderBy('views_count', 'desc')
            ->take(5)
            ->get();
        $popularPosts = $this->imageService->transformPostsCollection($popularPosts);

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
        $postData = $this->imageService->transformPostDetailWithMedia($post);

        $relatedPosts = Post::where('id', '!=', $post->id)
            ->where('category', $post->category)
            ->where('status', 'published')
            ->latest('published_at')
            ->take(3)
            ->with(['author', 'media'])
            ->get();
        $relatedPosts = $this->imageService->transformPostsCollection($relatedPosts);

        return Inertia::render('BeritaDetailPage', [
            'post' => $postData,
            'relatedPosts' => $relatedPosts
        ]);
    }
}
