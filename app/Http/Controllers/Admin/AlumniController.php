<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Services\ImageService;
use Inertia\Inertia;
use App\Http\Requests\AlumniStoreRequest;
use App\Http\Requests\AlumniUpdateRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlumniController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index()
    {
        $alumnis = Alumni::with('media')->orderBy('sort_order')->latest()->get();
        
        $transformedAlumnis = $alumnis->map(function ($alumni) {
            $data = $alumni->toArray();
            $data['testimonialImages'] = $this->imageService->getOrderedMediaData($alumni, 'testimonial_images');
            
            if ($alumni->content_type === 'video' && $alumni->video_source === 'upload') {
                $videoMedia = $alumni->getFirstMedia('videos');
                if ($videoMedia) {
                    $data['video_url'] = $videoMedia->getUrl();
                }
            }
            
            return $data;
        })->toArray();
        
        return Inertia::render('Admin/Alumni/Index', [
            'alumnis' => $transformedAlumnis
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Alumni/Create');
    }

    public function store(AlumniStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            if (isset($validated['testimonial'])) {
                $validated['testimonial'] = strip_tags($validated['testimonial']);
            }

            $alumniData = collect($validated)
                ->except(['testimonial_images', 'existing_testimonial_images', 'video_file', 'video_thumbnail'])
                ->toArray();

            $alumni = Alumni::create($alumniData);

            if ($request->hasFile('testimonial_images')) {
                $order = 0;
                foreach ($request->file('testimonial_images') as $image) {
                    $media = $alumni->addMedia($image)->toMediaCollection('testimonial_images');
                    // Set order for new images
                    $media->update(['order_column' => $order++]);
                }
            }

            if ($request->content_type === 'video') {
                if ($request->video_source === 'upload' && $request->hasFile('video_file')) {
                    $alumni->addMediaFromRequest('video_file')->toMediaCollection('videos');
                }

                if ($request->hasFile('video_thumbnail')) {
                    $alumni->addMediaFromRequest('video_thumbnail')->toMediaCollection('video_thumbnails');
                }
            }

            DB::commit();
            return redirect()->route('admin.alumni.index')->with('success', 'Alumni berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store alumni: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal menyimpan data alumni. ' . $e->getMessage()])->withInput();
        }
    }

    public function edit(Alumni $alumni)
    {
        return Inertia::render('Admin/Alumni/Edit', [
            'alumni' => $alumni->toArray()
        ]);
    }

    public function update(AlumniUpdateRequest $request, Alumni $alumni)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            if (isset($validated['testimonial'])) {
                $validated['testimonial'] = strip_tags($validated['testimonial']);
            }

            $alumniData = collect($validated)
                ->except(['testimonial_images', 'existing_testimonial_images', 'video_file', 'video_thumbnail'])
                ->toArray();

            $alumni->update($alumniData);

            $existingTestimonialImages = $request->input('existing_testimonial_images', []);
            if (!is_array($existingTestimonialImages)) {
                $existingTestimonialImages = [];
            }

            $existingMedia = $alumni->getMedia('testimonial_images');
            
            // Build a map of URL to media for matching
            $urlToMedia = [];
            foreach ($existingMedia as $media) {
                $mediaUrl = $this->imageService->getMediaUrl($media);
                $urlToMedia[$mediaUrl] = $media;
            }

            // Process existing images in order - keep and reorder
            $processedUrls = [];
            foreach ($existingTestimonialImages as $index => $url) {
                if (isset($urlToMedia[$url])) {
                    $media = $urlToMedia[$url];
                    $media->update(['order_column' => $index]);
                    $processedUrls[] = $url;
                }
            }

            // Delete any existing media not in the ordered list
            foreach ($existingMedia as $media) {
                $mediaUrl = $this->imageService->getMediaUrl($media);
                if (!in_array($mediaUrl, $processedUrls, true)) {
                    $media->delete();
                }
            }

            // Add new images at the end
            $newOrderStart = count($existingTestimonialImages);
            if ($request->hasFile('testimonial_images')) {
                $order = $newOrderStart;
                foreach ($request->file('testimonial_images') as $image) {
                    $media = $alumni->addMedia($image)->toMediaCollection('testimonial_images');
                    $media->update(['order_column' => $order++]);
                }
            }

            if ($request->content_type === 'video') {
                if ($request->video_source === 'upload' && $request->hasFile('video_file')) {
                    $alumni->clearMediaCollection('videos');
                    $alumni->addMediaFromRequest('video_file')->toMediaCollection('videos');
                }

                if ($request->hasFile('video_thumbnail')) {
                    $alumni->clearMediaCollection('video_thumbnails');
                    $alumni->addMediaFromRequest('video_thumbnail')->toMediaCollection('video_thumbnails');
                }
            }

            DB::commit();
            return redirect()->route('admin.alumni.index')->with('success', 'Alumni berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update alumni: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memperbarui data alumni. ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Alumni $alumni)
    {
        $alumni->delete();
        return redirect()->route('admin.alumni.index')->with('success', 'Alumni berhasil dihapus.');
    }
}
