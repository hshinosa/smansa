<?php

namespace App\Http\Requests;

use App\Models\Alumni;
use Illuminate\Foundation\Http\FormRequest;

class AlumniStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'graduation_year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'testimonial' => 'required_if:content_type,text|nullable|string',
            'content_type' => 'required|in:text,video',
            'testimonial_images' => 'nullable|array|max:10',
            'testimonial_images.*' => 'image|max:10240',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];

        if ($this->input('content_type') === 'video') {
            $rules['video_source'] = 'required|in:youtube,upload';
            
            if ($this->input('video_source') === 'youtube') {
                $rules['video_url'] = ['required', 'url', 'max:500', function ($attribute, $value, $fail) {
                    if (!Alumni::isYouTubeUrl($value)) {
                        $fail('URL harus berupa URL YouTube yang valid.');
                    }
                }];
            } else if ($this->input('video_source') === 'upload') {
                $rules['video_file'] = 'required|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime|max:51200';
            }
            
            $rules['video_thumbnail'] = 'nullable|image|max:10240';
        }

        return $rules;
    }
}
