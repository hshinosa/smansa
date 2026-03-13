<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeacherSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->guard()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string',
            'image_file' => 'nullable|image|max:10240',
        ];
    }
}
