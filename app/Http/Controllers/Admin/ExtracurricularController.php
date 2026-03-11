<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ExtracurricularRequest;
use Illuminate\Support\Facades\DB;

class ExtracurricularController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index()
    {
        $extracurriculars = Extracurricular::with('media')->orderBy('sort_order')->get();

        $transformedData = $extracurriculars->map(function ($extracurricular) {
            return $extracurricular->toArray();
        });

        return Inertia::render('Admin/Extracurriculars/Index', [
            'extracurriculars' => $transformedData
        ]);
    }

    public function store(ExtracurricularRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            $validated['description'] = strip_tags($validated['description'], '<b><i><u><p><br>');
            if (isset($validated['activity_description'])) {
                $validated['activity_description'] = strip_tags($validated['activity_description'], '<b><i><u><p><br>');
            }

            $images = [
                'image' => $request->file('image'),
                'bg_image' => $request->file('bg_image'),
                'profile_image' => $request->file('profile_image')
            ];
            unset($validated['image'], $validated['bg_image'], $validated['profile_image']);

            $extracurricular = Extracurricular::create($validated);

            if ($images['image']) {
                $extracurricular->addMedia($images['image'])->toMediaCollection('images');
            }
            if ($images['bg_image']) {
                $extracurricular->addMedia($images['bg_image'])->toMediaCollection('bg_images');
            }
            if ($images['profile_image']) {
                $extracurricular->addMedia($images['profile_image'])->toMediaCollection('profile_images');
            }

            DB::commit();
            return redirect()->route('admin.extracurriculars.index', ['tab' => $validated['type']])
                            ->with('success', 'Ekstrakurikuler berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store extracurricular: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal menambah data ekstrakurikuler.']);
        }
    }

    public function update(ExtracurricularRequest $request, Extracurricular $extracurricular)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            // Sanitization
            $validated['description'] = strip_tags($validated['description'] ?? '', '<b><i><u><p><br>');
            if (isset($validated['activity_description'])) {
                $validated['activity_description'] = strip_tags($validated['activity_description'], '<b><i><u><p><br>');
            }

            $images = [
                'image' => $request->file('image'),
                'bg_image' => $request->file('bg_image'),
                'profile_image' => $request->file('profile_image')
            ];
            unset($validated['image'], $validated['bg_image'], $validated['profile_image']);

            $extracurricular->update($validated);

            if ($images['image']) {
                $extracurricular->clearMediaCollection('images');
                $extracurricular->addMedia($images['image'])->toMediaCollection('images');
            }
            if ($images['bg_image']) {
                $extracurricular->clearMediaCollection('bg_images');
                $extracurricular->addMedia($images['bg_image'])->toMediaCollection('bg_images');
            }
            if ($images['profile_image']) {
                $extracurricular->clearMediaCollection('profile_images');
                $extracurricular->addMedia($images['profile_image'])->toMediaCollection('profile_images');
            }

            DB::commit();
            return redirect()->route('admin.extracurriculars.index', ['tab' => $validated['type']])
                            ->with('success', 'Ekstrakurikuler berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update extracurricular: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memperbarui data ekstrakurikuler.']);
        }
    }

    public function destroy(Extracurricular $extracurricular)
    {
        $activeTab = $extracurricular->type;
        $extracurricular->delete();

        return redirect()->route('admin.extracurriculars.index', ['tab' => $activeTab])
                        ->with('success', 'Ekstrakurikuler berhasil dihapus');
    }
}
