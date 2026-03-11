<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use App\Models\PtnAdmission;
use App\Models\PtnUniversity;

class PtnPdfParser
{
    protected $batchId;

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function parseAndSave($filePath)
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        // Split text into lines
        $lines = preg_split('/\r\n|\r|\n/', $text);
        
        // Aggregate counts: $counts[PtnName][ProdiName] = count
        $counts = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Split by tab first, fallback to multiple spaces
            $parts = preg_split('/\t/', $line);
            
            // If tabs didn't split well (less than 3 parts), try splitting by 2+ spaces
            if (count($parts) < 3) {
                $parts = preg_split('/\s{2,}/', $line);
            }

            // Expecting at least 3 parts: [Name/No] ... [Prodi] [PTN]
            if (count($parts) >= 3) {
                $rawPtn = trim(end($parts));
                $rawProdi = trim(prev($parts));

                // Basic validation: PTN shouldn't be too long or numeric
                if (strlen($rawPtn) > 50 || is_numeric($rawPtn)) continue;
                if ($rawPtn === 'PTN' || $rawProdi === 'PROGRAM STUDI') continue; // Header

                if (!isset($counts[$rawPtn])) {
                    $counts[$rawPtn] = [];
                }
                if (!isset($counts[$rawPtn][$rawProdi])) {
                    $counts[$rawPtn][$rawProdi] = 0;
                }
                $counts[$rawPtn][$rawProdi]++;
            }
        }

        // Process aggregations and save to DB
        $savedCount = 0;
        
        // Pre-load universities map
        $universities = PtnUniversity::all();
        $uniMap = [];
        foreach ($universities as $uni) {
            $uniMap[strtolower(trim($uni->name))] = $uni->id;
            if ($uni->short_name) {
                $uniMap[strtolower(trim($uni->short_name))] = $uni->id;
            }
        }

        foreach ($counts as $ptnName => $prodis) {
            $ptnId = $this->findPtnId($ptnName, $uniMap);

            // Create new PTN if not found
            if (!$ptnId) {
                $newUni = PtnUniversity::create([
                    'name' => $ptnName,
                    'is_active' => true
                ]);
                $ptnId = $newUni->id;
                $uniMap[strtolower($ptnName)] = $ptnId;
            }

            foreach ($prodis as $prodiName => $count) {
                PtnAdmission::updateOrCreate(
                    [
                        'batch_id' => $this->batchId,
                        'university_id' => $ptnId,
                        'program_studi' => $prodiName,
                    ],
                    [
                        'count' => $count
                    ]
                );
                $savedCount++; // Count distinct entries saved (PTN+Prodi combination)
            }
        }

        return $savedCount;
    }

    private function findPtnId($name, $map)
    {
        $key = strtolower(trim($name));
        return $map[$key] ?? null;
    }
}
