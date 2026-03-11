<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchoolProfileRequest extends FormRequest
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
            'content.title' => 'nullable|string|max:255',
            'content.description' => 'nullable|string',
            'content.image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'content.items' => 'nullable|array',
            'content.items.*.name' => 'nullable|string|max:255',
            'content.items.*.description' => 'nullable|string',
            'content.items.*.image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ];
    }
}
