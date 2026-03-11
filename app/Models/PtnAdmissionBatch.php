<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PtnAdmissionBatch extends Model
{
    use HasFactory;

    protected $table = 'ptn_admission_batches';

    protected $fillable = [
        'name',
        'type',
        'year',
        'total_students',
        'description',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
        'total_students' => 'integer',
        'year' => 'integer',
    ];

    public function admissions()
    {
        return $this->hasMany(PtnAdmission::class, 'batch_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('year')->orderBy('type')->orderBy('sort_order');
    }

    public function updateTotalStudents()
    {
        $this->total_students = $this->admissions()->sum('count');
        $this->save();
    }
}
