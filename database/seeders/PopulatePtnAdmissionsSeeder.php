<?php

namespace Database\Seeders;

use App\Models\PtnAdmissionBatch;
use App\Models\PtnUniversity;
use App\Models\PtnAdmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PopulatePtnAdmissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clear existing data
        PtnAdmission::truncate();
        PtnAdmissionBatch::truncate();
        
        // 2. Ensure Universities exist (should already be done by PtnUniversitySeeder)
        $itb = PtnUniversity::where('short_name', 'ITB')->first();
        $unpad = PtnUniversity::where('short_name', 'UNPAD')->first();
        $upi = PtnUniversity::where('short_name', 'UPI')->first();
        $ipb = PtnUniversity::where('short_name', 'IPB')->first();
        $ui = PtnUniversity::where('short_name', 'UI')->first();
        $ugm = PtnUniversity::where('short_name', 'UGM')->first();

        // 3. Create Batches
        $batch2024 = PtnAdmissionBatch::create([
            'name' => 'SNBP 2024',
            'type' => 'SNBP',
            'year' => 2024,
            'is_published' => true,
            'sort_order' => 1
        ]);

        $batch2025 = PtnAdmissionBatch::create([
            'name' => 'SNBP 2025',
            'type' => 'SNBP',
            'year' => 2025,
            'is_published' => true,
            'sort_order' => 2
        ]);

        // 4. Add Admissions for 2024
        if ($itb) {
            PtnAdmission::create(['batch_id' => $batch2024->id, 'university_id' => $itb->id, 'program_studi' => 'Teknik Informatika', 'count' => 5]);
            PtnAdmission::create(['batch_id' => $batch2024->id, 'university_id' => $itb->id, 'program_studi' => 'SBM', 'count' => 3]);
        }
        if ($unpad) {
            PtnAdmission::create(['batch_id' => $batch2024->id, 'university_id' => $unpad->id, 'program_studi' => 'Kedokteran', 'count' => 4]);
            PtnAdmission::create(['batch_id' => $batch2024->id, 'university_id' => $unpad->id, 'program_studi' => 'Hukum', 'count' => 12]);
        }
        if ($upi) {
            PtnAdmission::create(['batch_id' => $batch2024->id, 'university_id' => $upi->id, 'program_studi' => 'Pendidikan Matematika', 'count' => 15]);
        }

        // 5. Add Admissions for 2025
        if ($ui) {
            PtnAdmission::create(['batch_id' => $batch2025->id, 'university_id' => $ui->id, 'program_studi' => 'Psikologi', 'count' => 2]);
        }
        if ($ugm) {
            PtnAdmission::create(['batch_id' => $batch2025->id, 'university_id' => $ugm->id, 'program_studi' => 'Teknik Sipil', 'count' => 4]);
        }

        // 6. Update Total Students for each batch
        $batch2024->updateTotalStudents();
        $batch2025->updateTotalStudents();

        $this->command->info('PTN Admissions populated successfully.');
    }
}
