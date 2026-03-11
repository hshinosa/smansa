<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:photo,video',
            'file' => [
                'nullable',
                'file',
                'mimes:jpeg,png,jpg,webp,mp4,webm,mov',
                function ($attribute, $value, $fail) {
                    $max = $this->type === 'video' ? 20480 : 5120; // 20MB vs 5MB
                    if ($value && $value instanceof \Illuminate\Http\UploadedFile && $value->getSize() / 1024 > $max) {
                        $fail("Ukuran file " . ($this->type === 'video' ? 'video' : 'foto') . " tidak boleh lebih dari " . ($max / 1024) . " MB.");
                    }
                },
            ],
            'url' => 'nullable|string|max:255',
            'thumbnail_url' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'date' => 'nullable|date',
            'is_featured' => 'boolean',
            'is_external' => 'boolean',
        ];
    }
}
