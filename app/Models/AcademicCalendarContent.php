<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AcademicCalendarContent extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title',
        'calendar_image_url', // Backward compat
        'semester',
        'academic_year_start',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'semester' => 'integer',
        'academic_year_start' => 'integer',
        'sort_order' => 'integer',
    ];

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('large')
            ->width(1920)
            ->format('webp')
            ->quality(90)
            ->nonQueued();

        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(95)
            ->nonQueued();
    }

    public static function getDefaults(): array
    {
        return [
            'title' => 'Kalender Akademik SMA Negeri 1 Baleendah',
            'calendar_image_url' => '/images/kalender-akademik-default.jpg',
            'semester' => 1, // 1 for odd/ganjil
            'academic_year_start' => 2024, // 2024/2025
            'sort_order' => 0,
        ];
    }

    public function getAcademicYearAttribute()
    {
        return $this->academic_year_start.'/'.($this->academic_year_start + 1);
    }

    // Helper method to get semester name
    public function getSemesterNameAttribute()
    {
        return $this->semester == 1 ? 'Ganjil' : 'Genap';
    }

    public function scopeOrdered($query)
    {
        $columns = Schema::getColumnListing('academic_calendar_contents');
        if (in_array('academic_year_start', $columns)) {
            return $query->orderBy('academic_year_start', 'desc')
                ->orderBy('semester', 'asc')
                ->orderBy('sort_order', 'asc');
        } else {
            return $query->orderBy('id', 'desc')
                ->orderBy('sort_order', 'asc');
        }
    }
}
