<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PtnUniversity;
use App\Models\PtnAdmissionBatch;
use App\Models\PtnAdmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PtnAdmissionsImport;
use App\Services\PtnPdfParser;

use App\Http\Requests\PtnAdmissionRequest;

class PtnAdmissionController extends Controller
{
    public function index()
    {
        $batches = PtnAdmissionBatch::with(['admissions.university'])
            ->ordered()
            ->get()
            ->map(function ($batch) {
                $uniSummary = $batch->admissions
                    ->groupBy('university_id')
                    ->map(function ($items) {
                        $first = $items->first();
                        return [
                            'university_id' => $first->university_id,
                            'university_name' => $first->university->name ?? 'Unknown',
                            'university_color' => $first->university->color ?? '#6B7280',
                            'count' => $items->sum('count'),
                        ];
                    })
                    ->sortByDesc('count')
                    ->values();

                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'type' => $batch->type,
                    'year' => $batch->year,
                    'total_students' => $batch->total_students,
                    'is_published' => $batch->is_published,
                    'summaries' => $uniSummary,
                ];
            });

        $universities = PtnUniversity::active()->ordered()->get();

        $stats = [
            'total_batches' => PtnAdmissionBatch::count(),
            'total_admissions' => PtnAdmission::sum('count'),
            'total_universities' => PtnUniversity::active()->count(),
        ];

        return Inertia::render('Admin/PtnAdmissions/Index', [
            'batches' => $batches,
            'universities' => $universities,
            'stats' => $stats,
        ]);
    }

    public function storeBatch(PtnAdmissionRequest $request)
    {
        $validated = $request->validated();
        $validated['name'] = strip_tags($validated['name']);
        if (isset($validated['description'])) {
            $validated['description'] = strip_tags($validated['description']);
        }

        $batch = PtnAdmissionBatch::create($validated);

        return back()->with('success', 'Batch berhasil ditambahkan.');
    }

    public function updateBatch(PtnAdmissionRequest $request, PtnAdmissionBatch $batch)
    {
        $validated = $request->validated();
        $validated['name'] = strip_tags($validated['name']);
        if (isset($validated['description'])) {
            $validated['description'] = strip_tags($validated['description']);
        }

        $batch->update($validated);

        return back()->with('success', 'Batch berhasil diperbarui.');
    }

    public function destroyBatch(PtnAdmissionBatch $batch)
    {
        $batch->delete();

        return back()->with('success', 'Batch berhasil dihapus.');
    }

    public function showBatch(PtnAdmissionBatch $batch)
    {
        $admissions = $batch->admissions()
            ->with('university')
            ->orderBy('university_id')
            ->get()
            ->map(function ($admission) {
                return [
                    'id' => $admission->id,
                    'university_id' => $admission->university_id,
                    'university_name' => $admission->university->name ?? 'Unknown',
                    'program_studi' => $admission->program_studi,
                    'count' => $admission->count,
                ];
            });

        $universities = PtnUniversity::active()->ordered()->get();

        return Inertia::render('Admin/PtnAdmissions/Show', [
            'batch' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'type' => $batch->type,
                'year' => $batch->year,
                'total_students' => $batch->total_students,
                'is_published' => $batch->is_published,
                'description' => $batch->description,
            ],
            'admissions' => $admissions,
            'universities' => $universities,
        ]);
    }

    public function storeAdmission(PtnAdmissionRequest $request, PtnAdmissionBatch $batch)
    {
        $validated = $request->validated();
        $validated['program_studi'] = strip_tags($validated['program_studi']);
        $validated['batch_id'] = $batch->id;

        PtnAdmission::updateOrCreate(
            [
                'batch_id' => $batch->id,
                'university_id' => $validated['university_id'],
                'program_studi' => $validated['program_studi'],
            ],
            [
                'count' => $validated['count']
            ]
        );

        return back()->with('success', 'Data berhasil disimpan.');
    }

    public function updateAdmission(PtnAdmissionRequest $request, PtnAdmission $admission)
    {
        $validated = $request->validated();
        $validated['program_studi'] = strip_tags($validated['program_studi']);

        $admission->update($validated);

        return back()->with('success', 'Data berhasil diperbarui.');
    }

    public function destroyAdmission(PtnAdmission $admission)
    {
        $admission->delete();

        return back()->with('success', 'Data berhasil dihapus.');
    }

    public function storeUniversity(PtnAdmissionRequest $request)
    {
        $validated = $request->validated();
        $validated['name'] = strip_tags($validated['name']);
        if (isset($validated['short_name'])) {
            $validated['short_name'] = strip_tags($validated['short_name']);
        }

        PtnUniversity::create($validated);

        return back()->with('success', 'PTN berhasil ditambahkan.');
    }

    public function updateUniversity(PtnAdmissionRequest $request, PtnUniversity $university)
    {
        $validated = $request->validated();
        $validated['name'] = strip_tags($validated['name']);
        if (isset($validated['short_name'])) {
            $validated['short_name'] = strip_tags($validated['short_name']);
        }

        $university->update($validated);

        return back()->with('success', 'PTN berhasil diperbarui.');
    }

    public function destroyUniversity(PtnUniversity $university)
    {
        if ($university->admissions()->count() > 0) {
            return back()->with('error', 'PTN tidak dapat dihapus karena masih memiliki data penerimaan.');
        }

        $university->delete();

        return back()->with('success', 'PTN berhasil dihapus.');
    }

    public function importExcel(Request $request, PtnAdmissionBatch $batch)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv,pdf|max:5120',
        ]);

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            if (strtolower($extension) === 'pdf') {
                $parser = new PtnPdfParser($batch->id);
                $parser->parseAndSave($file->getPathname());
            } else {
                Excel::import(new PtnAdmissionsImport($batch->id), $file);
            }

            $batch->updateTotalStudents(); // Recalculate totals
            return back()->with('success', 'Data berhasil diimport.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimport file: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_serapan_ptn.csv"',
        ];

        $columns = ['ptn', 'jurusan', 'jumlah'];
        $example1 = ['Universitas Padjadjaran', 'Teknik Informatika', '5'];
        $example2 = ['Institut Teknologi Bandung', 'Sekolah Farmasi', '2'];

        $callback = function () use ($columns, $example1, $example2) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $example1);
            fputcsv($file, $example2);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
