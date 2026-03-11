<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcademicCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'calendar_image' => ($this->isMethod('POST') ? 'required' : 'nullable') . '|file|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            'semester' => 'required|integer|in:1,2',
            'academic_year_start' => 'required|integer|min:2000|max:2100',
            'sort_order' => 'integer|min:0',
        ];
    }
}
