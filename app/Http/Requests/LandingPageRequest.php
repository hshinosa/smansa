<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LandingPageRequest extends FormRequest
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

        if ($section === 'hero') {
            $rules['content.title_line1'] = 'required|string|max:100';
            $rules['content.title_line2'] = 'required|string|max:100';
            $rules['content.hero_text'] = 'required|string|max:500';
            $rules['hero.background_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240';
            $rules['hero.student_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240';
            $rules['content.stats'] = 'nullable|array';
            $rules['content.stats.*.label'] = 'required|string|max:50';
            $rules['content.stats.*.value'] = 'required|string|max:50';
            $rules['content.stats.*.icon_name'] = 'required|string|max:50';
        } elseif ($section === 'about_lp') {
            $rules['content.title'] = 'required|string|max:150';
            $rules['content.description_html'] = 'required|string|max:5000';
            $rules['about_lp.image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240';
        } elseif ($section === 'kepsek_welcome_lp') {
            $rules['content.title'] = 'required|string|max:150';
            $rules['content.kepsek_name'] = 'required|string|max:100';
            $rules['content.kepsek_title'] = 'required|string|max:100';
            $rules['kepsek_welcome_lp.kepsek_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240';
            $rules['content.welcome_text_html'] = 'required|string|max:10000';
        }

        return $rules;
    }
}
