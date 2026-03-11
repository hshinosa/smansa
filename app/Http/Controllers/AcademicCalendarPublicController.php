<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendarContent;
use App\Services\ImageService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AcademicCalendarPublicController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display the academic calendar page for public viewing
     */
    public function index()
    {
        // Cache academic calendar data for 1 hour (60 minutes)
        // Calendar doesn't change often
        $data = Cache::remember('academic_calendar_page', 60 * 60, function () {
            // Get all calendar contents for display
            $calendarContents = AcademicCalendarContent::ordered()
                ->where('is_active', true)
                ->with('media')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'calendar_image_url' => $item->calendar_image_url,
                        'image' => $this->imageService->getFirstMediaData($item, 'calendar_images'),
                        'semester' => $item->semester,
                        'semester_name' => $item->semester_name,
                        'academic_year_start' => $item->academic_year_start,
                        'academic_year' => $item->academic_year,
                        'is_active' => $item->is_active,
                        'sort_order' => $item->sort_order,
                    ];
                });

            // Get current academic year
            $latestCalendar = AcademicCalendarContent::where('is_active', true)
                ->orderBy('academic_year_start', 'desc')
                ->first();

            $currentAcademicYear = $latestCalendar
                ? $latestCalendar->academic_year_start.'/'.($latestCalendar->academic_year_start + 1)
                : '2024/2025';
                
            return [
                'calendarContents' => $calendarContents,
                'currentAcademicYear' => $currentAcademicYear,
            ];
        });

        return Inertia::render('AcademicCalendarPage', [
            'calendarContents' => $data['calendarContents'],
            'currentAcademicYear' => $data['currentAcademicYear'],
        ]);
    }
}
