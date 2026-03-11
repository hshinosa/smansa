<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ProgramRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgramController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index()
    {
        $programs = Program::with('media')->orderBy('sort_order')->get();
        return Inertia::render('Admin/Programs/Index', [
            'programs' => $this->imageService->transformCollectionWithMedia($programs, ['program_images'])
        ]);
    }

    public function store(ProgramRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            if (isset($validated['description'])) {
                $validated['description'] = strip_tags($validated['description'], '<b><i><u><p><br>');
            }

            unset($validated['image']);

            $program = Program::create($validated);

            if ($request->hasFile('image')) {
                $program->addMediaFromRequest('image')->toMediaCollection('program_images');
                
                $media = $program->getFirstMedia('program_images');
                if ($media) {
                    $program->update(['image_url' => $media->getUrl()]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Program berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store program: ' . $e->getMessage());
            return redirect()->back()->withErrors(['general' => 'Gagal menambah program.']);
        }
    }

    public function update(ProgramRequest $request, Program $program)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            if (isset($validated['description'])) {
                $validated['description'] = strip_tags($validated['description'], '<b><i><u><p><br>');
            }

            unset($validated['image']);

            $program->update($validated);

            if ($request->hasFile('image')) {
                $program->clearMediaCollection('program_images');
                $program->addMediaFromRequest('image')->toMediaCollection('program_images');
                
                $media = $program->getFirstMedia('program_images');
                if ($media) {
                    $program->update(['image_url' => $media->getUrl()]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Program berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update program: ' . $e->getMessage());
            return redirect()->back()->withErrors(['general' => 'Gagal memperbarui program.']);
        }
    }

    public function destroy(Program $program)
    {
        $program->delete();
        return redirect()->back()->with('success', 'Program berhasil dihapus');
    }
}
