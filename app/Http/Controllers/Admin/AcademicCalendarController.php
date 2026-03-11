<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ActivityLogger;
use App\Http\Controllers\Controller;
use App\Models\AcademicCalendarContent;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\AcademicCalendarRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcademicCalendarController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index(Request $request)
    {
        $query = AcademicCalendarContent::query()->with('media');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('academic_year_start', 'like', "%{$search}%");
            });
        }

        $contents = $query->ordered()->paginate(10)->withQueryString();
        
        $contents->through(function ($item) {
            return $this->imageService->transformModelWithMedia($item, ['calendar_images']);
        });

        return Inertia::render('Admin/AcademicCalendarContentPage', [
            'contents' => $contents,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function store(AcademicCalendarRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $validated['title'] = strip_tags($validated['title']);

            $content = AcademicCalendarContent::create($validated);

            if ($request->hasFile('calendar_image')) {
                $content->addMediaFromRequest('calendar_image')->toMediaCollection('calendar_images');
                $media = $content->getFirstMedia('calendar_images');
                if ($media) {
                    $content->update(['calendar_image_url' => $media->getUrl()]);
                }
            }

            DB::commit();
            ActivityLogger::log('create', "Menambahkan konten kalender akademik: {$content->title}");

            return redirect()->route('admin.academic-calendar.index')->with('success', 'Konten kalender akademik berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store academic calendar: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal menambah kalender akademik.']);
        }
    }

    public function update(AcademicCalendarRequest $request, AcademicCalendarContent $academic_calendar)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $validated['title'] = strip_tags($validated['title']);

            $academic_calendar->update($validated);

            if ($request->hasFile('calendar_image')) {
                $academic_calendar->clearMediaCollection('calendar_images');
                $academic_calendar->addMediaFromRequest('calendar_image')->toMediaCollection('calendar_images');
                $media = $academic_calendar->getFirstMedia('calendar_images');
                if ($media) {
                    $academic_calendar->update(['calendar_image_url' => $media->getUrl()]);
                }
            }

            DB::commit();
            ActivityLogger::log('update', "Memperbarui konten kalender akademik: {$academic_calendar->title}");

            return redirect()->route('admin.academic-calendar.index')->with('success', 'Konten kalender akademik berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update academic calendar: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memperbarui kalender akademik.']);
        }
    }

    public function destroy(AcademicCalendarContent $academic_calendar)
    {
        $title = $academic_calendar->title;
        $academic_calendar->delete();

        ActivityLogger::log('delete', "Menghapus konten kalender akademik: {$title}");

        return redirect()->route('admin.academic-calendar.index')->with('success', 'Konten kalender akademik berhasil dihapus.');
    }

    public function setActive(AcademicCalendarContent $content)
    {
        $content->update(['is_active' => !$content->is_active]);
        $status = $content->is_active ? 'diaktifkan' : 'dinonaktifkan';
        ActivityLogger::log('update', "Mengubah status kalender akademik {$content->title} menjadi {$status}");

        return redirect()->route('admin.academic-calendar.index')->with('success', "Kalender akademik berhasil {$status}.");
    }
}
