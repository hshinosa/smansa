<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use App\Http\Requests\GalleryRequest;
use Illuminate\Support\Facades\DB;

class GalleryController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index()
    {
        $galleries = Gallery::with('media')->latest()->get();

        return Inertia::render('Admin/Galleries/Index', [
            'galleries' => $galleries->map(function ($gallery) {
                $data = $this->imageService->transformModelWithMedia($gallery, ['images', 'videos']);
                
                if (isset($data['url']) && !empty($data['url'])) {
                    if (!str_starts_with($data['url'], 'http') && !str_starts_with($data['url'], '/')) {
                        $data['url'] = '/images/' . $data['url'];
                    }
                }
                
                return $data;
            })
        ]);
    }

    public function store(GalleryRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            if (empty($validated['date'])) {
                $validated['date'] = now();
            }

            if (isset($validated['description'])) {
                $validated['description'] = strip_tags($validated['description']);
            }

            // Boolean fix for FormData
            $validated['is_featured'] = filter_var($request->input('is_featured', false), FILTER_VALIDATE_BOOLEAN);
            $validated['is_external'] = filter_var($request->input('is_external', false), FILTER_VALIDATE_BOOLEAN);

            if ($request->hasFile('file')) {
                $validated['url'] = 'temp'; // Placeholder
                $validated['is_external'] = false; 

                $gallery = Gallery::create($validated);
                $collection = $request->type === 'video' ? 'videos' : 'images';

                $gallery->addMediaFromRequest('file')->toMediaCollection($collection);
                $media = $gallery->getFirstMedia($collection);
                $gallery->update(['url' => $media->getUrl()]);
            } else {
                $validated['url'] = $validated['url'] ?? '';
                Gallery::create($validated);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Galeri berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store gallery: ' . $e->getMessage());
            return redirect()->back()->withErrors(['general' => 'Gagal menambahkan galeri: ' . $e->getMessage()]);
        }
    }

    public function update(GalleryRequest $request, Gallery $gallery)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            if (isset($validated['description'])) {
                $validated['description'] = strip_tags($validated['description']);
            }

            // Boolean fix for FormData / Native Fetch
            $validated['is_featured'] = filter_var($request->input('is_featured', $gallery->is_featured), FILTER_VALIDATE_BOOLEAN);
            $validated['is_external'] = filter_var($request->input('is_external', $gallery->is_external), FILTER_VALIDATE_BOOLEAN);

            if ($request->hasFile('file')) {
                $gallery->clearMediaCollection('images');
                $gallery->clearMediaCollection('videos');

                $collection = $request->type === 'video' ? 'videos' : 'images';
                $gallery->addMediaFromRequest('file')->toMediaCollection($collection);
                
                $media = $gallery->getFirstMedia($collection);
                $validated['url'] = $media->getUrl();
                $validated['is_external'] = false;
            }

            $gallery->update($validated);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'gallery' => $gallery->fresh()]);
            }

            return redirect()->back()->with('success', 'Galeri berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update gallery: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            
            return redirect()->back()->withErrors(['general' => 'Gagal memperbarui galeri.']);
        }
    }

    public function destroy(Gallery $gallery)
    {
        $gallery->delete();
        return redirect()->back()->with('success', 'Galeri berhasil dihapus');
    }
}
