<?php

namespace App\Imports;

use App\Models\PtnAdmission;
use App\Models\PtnUniversity;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PtnAdmissionsImport implements ToCollection, WithHeadingRow
{
    protected $batchId;

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function collection(Collection $rows)
    {
        // Pre-load universities
        $universities = PtnUniversity::all();
        $uniMap = [];
        foreach ($universities as $uni) {
            $uniMap[strtolower(trim($uni->name))] = $uni->id;
            if ($uni->short_name) {
                $uniMap[strtolower(trim($uni->short_name))] = $uni->id;
            }
        }

        foreach ($rows as $row) {
            // Flexible column names
            $ptnName = $row['ptn'] ?? $row['universitas'] ?? $row['perguruan_tinggi'] ?? null;
            $prodiName = $row['jurusan'] ?? $row['program_studi'] ?? $row['prodi'] ?? 'Umum';
            $count = $row['jumlah'] ?? $row['total'] ?? $row['count'] ?? 0;

            if (empty($ptnName) || empty($count)) {
                continue;
            }

            $ptnId = $this->findPtnId($ptnName, $uniMap);

            if (!$ptnId) {
                $newUni = PtnUniversity::create([
                    'name' => trim($ptnName),
                    'is_active' => true
                ]);
                $ptnId = $newUni->id;
                $uniMap[strtolower(trim($ptnName))] = $ptnId;
            }

            // Update or Create entry
            PtnAdmission::updateOrCreate(
                [
                    'batch_id' => $this->batchId,
                    'university_id' => $ptnId,
                    'program_studi' => trim($prodiName),
                ],
                [
                    'count' => (int) $count
                ]
            );
        }
    }

    private function findPtnId($name, $map)
    {
        $key = strtolower(trim($name));
        return $map[$key] ?? null;
    }
}
