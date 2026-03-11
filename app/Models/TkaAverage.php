<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TkaAverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'exam_type',
        'subject_name',
        'average_score',
    ];

    protected $casts = [
        'average_score' => 'double',
    ];
}
