<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        $section = $this->input('section');
        $rules = [
            'section' => 'required|string',
            'content' => 'required|array',
        ];

        if ($section === 'general') {
            $rules['content.site_name'] = 'required|string|max:255';
            $rules['content.email'] = 'nullable|email|max:255';
            $rules['content.site_logo'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240';
            $rules['content.hero_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240';
        }

        return $rules;
    }
}
