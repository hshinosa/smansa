<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\SiteSetting;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\TeacherRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeacherController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index()
    {
        $settings = SiteSetting::where('section_key', 'hero_teachers')->first();
        $hero = SiteSetting::getContent('hero_teachers', $settings ? $settings->content : null);

        return Inertia::render('Admin/Teachers/Index', [
            'teachers' => Teacher::with('media')->latest()->get(),
            'currentSettings' => $hero
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string',
            'image_file' => 'nullable|image|max:10240',
        ]);

        try {
            DB::beginTransaction();

            $settings = SiteSetting::firstOrCreate(['section_key' => 'hero_teachers']);
            $content = $settings->content ?? SiteSetting::getDefaults('hero_teachers');

            $content['title'] = $validated['title'];
            $content['subtitle'] = $validated['subtitle'];

            if ($request->hasFile('image_file')) {
                $settings->clearMediaCollection('hero_bg');
                $settings->addMediaFromRequest('image_file')->toMediaCollection('hero_bg');
                $media = $settings->getFirstMedia('hero_bg');
                if ($media) {
                    $content['image'] = $media->getUrl();
                }
            }

            $settings->content = $content;
            $settings->save();

            DB::commit();
            SiteSetting::forgetCache();

            return redirect()->back()->with('success', 'Pengaturan hero berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update teacher settings: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memperbarui pengaturan hero.']);
        }
    }

    public function store(TeacherRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            if (isset($validated['bio'])) {
                $validated['bio'] = strip_tags($validated['bio'], '<b><i><u><p><br>');
            }

            unset($validated['image']);

            $teacher = Teacher::create($validated);

            if ($request->hasFile('image')) {
                $teacher->addMediaFromRequest('image')->toMediaCollection('photos');
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data Guru/Staff berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store teacher: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal menambah data Guru/Staff.']);
        }
    }

    public function update(TeacherRequest $request, Teacher $teacher)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            if (isset($validated['bio'])) {
                $validated['bio'] = strip_tags($validated['bio'], '<b><i><u><p><br>');
            }

            unset($validated['image']);

            $teacher->update($validated);

            if ($request->hasFile('image')) {
                $teacher->clearMediaCollection('photos');
                $teacher->addMediaFromRequest('image')->toMediaCollection('photos');
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data Guru/Staff berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update teacher: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memperbarui data Guru/Staff.']);
        }
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();
        return redirect()->back()->with('success', 'Data Guru/Staff berhasil dihapus');
    }
}
