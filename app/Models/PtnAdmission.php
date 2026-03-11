<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PtnAdmission extends Model
{
    use HasFactory;

    protected $table = 'ptn_admissions';

    protected $fillable = [
        'batch_id',
        'university_id',
        'program_studi',
        'count',
    ];

    protected $casts = [
        'count' => 'integer',
    ];

    public function batch()
    {
        return $this->belongsTo(PtnAdmissionBatch::class, 'batch_id');
    }

    public function university()
    {
        return $this->belongsTo(PtnUniversity::class, 'university_id');
    }

    protected static function booted()
    {
        static::saved(function ($admission) {
            $admission->batch->updateTotalStudents();
        });

        static::deleted(function ($admission) {
            if ($admission->batch) {
                $admission->batch->updateTotalStudents();
            }
        });
    }
}
