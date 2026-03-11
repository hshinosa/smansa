<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\HtmlSanitizer;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\PostRequest;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index()
    {
        $posts = Post::with(['author', 'media'])->latest()->get();
        
        $scrapedFeeds = DB::table('sc_raw_news_feeds')
            ->whereIn('processed_post_id', $posts->pluck('id'))
            ->get()
            ->keyBy('processed_post_id');

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts->map(function ($post) use ($scrapedFeeds) {
                $data = $this->imageService->transformModelWithMedia($post, ['featured', 'gallery']);
                
                $feed = $scrapedFeeds->get($post->id);
                if ($feed && !empty($feed->image_paths)) {
                    $paths = json_decode($feed->image_paths, true) ?? [];
                    $scrapedImages = [];
                    foreach ($paths as $path) {
                        $cleanPath = ltrim($path, '/\\\\');
                        if (str_starts_with($cleanPath, 'downloads/')) {
                            $cleanPath = substr($cleanPath, 10);
                        }
                        $cleanPath = ltrim($cleanPath, '/');
                        $scrapedImages[] = '/scraped-images/' . $cleanPath;
                    }
                    $data['scraped_images'] = $scrapedImages;
                }
                
                return $data;
            })
        ]);
    }

    public function store(PostRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $validated['content'] = HtmlSanitizer::sanitize($validated['content']);
            $validated['slug'] = Str::slug($request->title) . '-' . uniqid();
            $validated['author_id'] = Auth::guard('admin')->id();
            
            if (!$request->published_at && $request->status === 'published') {
                $validated['published_at'] = now();
            }

            unset($validated['featured_image'], $validated['new_images'], $validated['existing_images']);
            
            $post = Post::create($validated);

            if ($request->hasFile('featured_image')) {
                $post->addMediaFromRequest('featured_image')->toMediaCollection('featured');
            }

            if ($request->hasFile('new_images')) {
                foreach ($request->file('new_images') as $image) {
                    $post->addMedia($image)->toMediaCollection('gallery');
                }
            }

            DB::commit();
            return redirect()->route('admin.posts.index')->with('success', 'Berita berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store post: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal membuat berita.']);
        }
    }

    public function update(PostRequest $request, Post $post)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $validated['content'] = HtmlSanitizer::sanitize($validated['content']);

            if ($request->title !== $post->title) {
                $validated['slug'] = Str::slug($request->title) . '-' . uniqid();
            }

            if ($validated['status'] === 'published' && empty($validated['published_at'])) {
                $validated['published_at'] = $post->published_at ?? now();
            }

            if ($validated['status'] === 'draft') {
                $validated['published_at'] = null;
            }

            unset($validated['featured_image'], $validated['new_images'], $validated['existing_images']);

            if ($request->hasFile('featured_image')) {
                $post->clearMediaCollection('featured');
                $post->addMediaFromRequest('featured_image')->toMediaCollection('featured');
            }

            $keptUrls = json_decode($request->input('existing_images', '[]'), true) ?? [];
            $existingMedia = $post->getMedia('gallery');
            
            foreach ($existingMedia as $media) {
                if (!in_array($media->getUrl(), $keptUrls)) {
                     $media->delete();
                }
            }
            
            foreach ($keptUrls as $url) {
                if (str_starts_with($url, '/scraped-images/')) {
                    $matchFound = false;
                    foreach ($existingMedia as $m) {
                        if (str_contains($m->getUrl(), basename($url))) {
                            $matchFound = true;
                            break;
                        }
                    }
                    
                    if (!$matchFound) {
                        $relativePath = str_replace('/scraped-images/', '', $url);
                        $fullPath = base_path('instagram-scraper/downloads/' . $relativePath);
                        if (file_exists($fullPath) && !str_contains($relativePath, '..')) {
                            $post->addMedia($fullPath)->preservingOriginal()->toMediaCollection('gallery');
                        }
                    }
                }
            }
            
            if ($request->hasFile('new_images')) {
                foreach ($request->file('new_images') as $image) {
                    $post->addMedia($image)->toMediaCollection('gallery');
                }
            }

            $post->update($validated);

            DB::commit();
            return redirect()->route('admin.posts.index')->with('success', 'Berita berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update post: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memperbarui berita.']);
        }
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return redirect()->back()->with('success', 'Berita berhasil dihapus');
    }
}
