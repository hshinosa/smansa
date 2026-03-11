<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TkaAverageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'academic_year' => 'required|string|max:20',
            'exam_type' => 'required|string|max:50',
            'subject_name' => 'required|string|max:100',
            'average_score' => 'required|numeric|min:0|max:1000',
        ];
    }
}
