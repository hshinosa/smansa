<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PtnAdmission;
use App\Models\PtnAdmissionBatch;
use App\Models\PtnUniversity;
use App\Models\TkaAverage;
use Inertia\Inertia;

/**
 * Controller for public achievement pages (PTN admission & TKA results)
 * Refactored from routes/web.php closure
 */
class PrestasiController extends Controller
{
    public function index()
    {
        $batches = PtnAdmissionBatch::with(['admissions.university'])
            ->published()
            ->ordered()
            ->get()
            ->map(function ($batch) {
                $byPTN = $batch->admissions
                    ->groupBy('university_id')
                    ->map(function ($items) {
                        $first = $items->first();
                        $majors = $items->map(function ($item) {
                            return [
                                'name' => $item->program_studi,
                                'count' => $item->count
                            ];
                        })->sortByDesc('count')->values();

                        return [
                            'id' => $first->university_id,
                            'name' => $first->university->short_name ?? $first->university->name,
                            'fullName' => $first->university->name,
                            'count' => $items->sum('count'),
                            'color' => $first->university->color ?? '#6B7280',
                            'majors' => $majors
                        ];
                    })
                    ->sortByDesc('count')
                    ->values();

                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'type' => $batch->type,
                    'year' => $batch->year,
                    'total' => $batch->total_students,
                    'byPTN' => $byPTN,
                ];
            });

        $universities = PtnUniversity::active()
            ->ordered()
            ->get()
            ->map(function ($uni) {
                return [
                    'id' => $uni->id,
                    'name' => $uni->name,
                    'shortName' => $uni->short_name,
                    'color' => $uni->color,
                ];
            });

        $totalAdmissions = PtnAdmission::sum('count');
        $totalPtn = PtnAdmission::distinct('university_id')->count('university_id');

        $ptnFavorites = PtnAdmission::with('university')
            ->selectRaw('university_id, SUM(count) as total')
            ->groupBy('university_id')
            ->orderByDesc('total')
            ->take(4)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->university->short_name ?? $item->university->name,
                    'fullName' => $item->university->name,
                    'total' => (int) $item->total,
                    'color' => $item->university->color,
                ];
            });

        $tkaGroups = TkaAverage::select('academic_year', 'exam_type')
            ->groupBy('academic_year', 'exam_type')
            ->orderByDesc('academic_year')
            ->get()
            ->map(function ($group) {
                $subjects = TkaAverage::where('academic_year', $group->academic_year)
                    ->where('exam_type', $group->exam_type)
                    ->get();
                
                return [
                    'academic_year' => $group->academic_year,
                    'exam_type' => $group->exam_type,
                    'subjects' => $subjects
                ];
            });

        return Inertia::render('PrestasiAkademikPage', [
            'batches' => $batches,
            'universities' => $universities,
            'stats' => [
                'totalAdmissions' => (int) $totalAdmissions,
                'totalPtn' => $totalPtn,
            ],
            'ptnFavorites' => $ptnFavorites,
            'tkaGroups' => $tkaGroups
        ]);
    }

    public function serapanPtn()
    {
        $batches = PtnAdmissionBatch::with(['admissions.university'])
            ->published()
            ->ordered()
            ->get()
            ->map(function ($batch) {
                // Group by University for Pie Chart & Details
                $byPTN = $batch->admissions
                    ->groupBy('university_id')
                    ->map(function ($items) {
                        $first = $items->first();
                        
                        // Group majors within this university
                        $majors = $items->map(function ($item) {
                            return [
                                'name' => $item->program_studi,
                                'count' => $item->count
                            ];
                        })->sortByDesc('count')->values();

                        return [
                            'id' => $first->university_id,
                            'name' => $first->university->short_name ?? $first->university->name,
                            'fullName' => $first->university->name,
                            'count' => $items->sum('count'),
                            'color' => $first->university->color ?? '#6B7280',
                            'majors' => $majors
                        ];
                    })
                    ->sortByDesc('count')
                    ->values();

                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'type' => $batch->type,
                    'year' => $batch->year,
                    'total' => $batch->total_students,
                    'byPTN' => $byPTN,
                ];
            });

        $universities = PtnUniversity::active()
            ->ordered()
            ->get()
            ->map(function ($uni) {
                return [
                    'id' => $uni->id,
                    'name' => $uni->name,
                    'shortName' => $uni->short_name,
                    'color' => $uni->color,
                ];
            });

        $totalAdmissions = PtnAdmission::sum('count');
        
        // Count unique universities that have admissions
        $totalPtn = PtnAdmission::distinct('university_id')->count('university_id');

        // Top 4 Favorite PTNs across all batches
        $ptnFavorites = PtnAdmission::with('university')
            ->selectRaw('university_id, SUM(count) as total')
            ->groupBy('university_id')
            ->orderByDesc('total')
            ->take(4)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->university->short_name ?? $item->university->name,
                    'fullName' => $item->university->name,
                    'total' => (int) $item->total,
                    'color' => $item->university->color,
                ];
            });

        return Inertia::render('DataSerapanPTNPage', [
            'batches' => $batches,
            'universities' => $universities,
            'stats' => [
                'totalAdmissions' => (int) $totalAdmissions,
                'totalPtn' => $totalPtn,
            ],
            'ptnFavorites' => $ptnFavorites,
        ]);
    }

    /**
     * Display the TKA results page
     */
    public function hasilTka()
    {
        $tkaGroups = TkaAverage::select('academic_year', 'exam_type')
            ->groupBy('academic_year', 'exam_type')
            ->orderByDesc('academic_year')
            ->get()
            ->map(function ($group) {
                $subjects = TkaAverage::where('academic_year', $group->academic_year)
                    ->where('exam_type', $group->exam_type)
                    ->get();
                
                return [
                    'academic_year' => $group->academic_year,
                    'exam_type' => $group->exam_type,
                    'subjects' => $subjects
                ];
            });

        return Inertia::render('HasilTkaPage', [
            'tkaGroups' => $tkaGroups
        ]);
    }
}
