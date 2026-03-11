<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PtnUniversity extends Model
{
    use HasFactory;

    protected $table = 'ptn_universities';

    protected $fillable = [
        'name',
        'short_name',
        'color',
        'logo_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function admissions()
    {
        return $this->hasMany(PtnAdmission::class, 'university_id');
    }

    public function summaries()
    {
        return $this->hasMany(PtnAdmissionSummary::class, 'university_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
