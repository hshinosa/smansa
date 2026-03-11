<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CurriculumUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'section' => 'required|string',
            'content' => 'required|array',
            'media' => 'nullable|file|image|max:10240', // Max 10MB
        ];
    }
}
