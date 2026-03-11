<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TkaAverage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\TkaAverageRequest;

class TkaAverageController extends Controller
{
    public function index()
    {
        $groups = TkaAverage::select('academic_year', 'exam_type')
            ->selectRaw('count(*) as subject_count')
            ->selectRaw('avg(average_score) as overall_average')
            ->groupBy('academic_year', 'exam_type')
            ->orderByDesc('academic_year')
            ->get();

        return Inertia::render('Admin/TkaAverages/Index', [
            'groups' => $groups
        ]);
    }

    public function show($academic_year, $exam_type)
    {
        $subjects = TkaAverage::where('academic_year', $academic_year)
            ->where('exam_type', $exam_type)
            ->orderBy('subject_name')
            ->get();

        return Inertia::render('Admin/TkaAverages/Show', [
            'academic_year' => $academic_year,
            'exam_type' => $exam_type,
            'subjects' => $subjects
        ]);
    }

    public function store(TkaAverageRequest $request)
    {
        $validated = $request->validated();
        $validated['subject_name'] = strip_tags($validated['subject_name']);

        TkaAverage::updateOrCreate(
            [
                'academic_year' => $validated['academic_year'],
                'exam_type' => $validated['exam_type'],
                'subject_name' => $validated['subject_name'],
            ],
            ['average_score' => $validated['average_score']]
        );

        return back()->with('success', 'Data berhasil disimpan.');
    }

    public function update(TkaAverageRequest $request, $id)
    {
        $validated = $request->validated();
        $validated['subject_name'] = strip_tags($validated['subject_name']);

        $item = TkaAverage::findOrFail($id);
        $item->update([
            'subject_name' => $validated['subject_name'],
            'average_score' => $validated['average_score'],
        ]);

        return back()->with('success', 'Data diperbarui.');
    }

    public function destroy($id)
    {
        TkaAverage::findOrFail($id)->delete();
        return back()->with('success', 'Data dihapus.');
    }
    
    // Bulk delete for a group
    public function destroyGroup(Request $request)
    {
        $year = $request->input('academic_year');
        $type = $request->input('exam_type');
        
        TkaAverage::where('academic_year', $year)
            ->where('exam_type', $type)
            ->delete();
            
        return redirect()->route('admin.tka-averages.index')->with('success', 'Data grup ujian berhasil dihapus.');
    }
}
