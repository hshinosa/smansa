<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgramStudiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        $rules = [
            'program_name' => 'required|string|in:mipa,ips,bahasa',
            'thumbnail_card' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ];

        $sectionKeys = ['hero', 'core_subjects', 'facilities', 'career_paths'];
        foreach ($sectionKeys as $key) {
            if ($this->has($key)) {
                if ($key === 'hero') {
                    $rules["{$key}.title"] = 'required|string|max:200';
                    $rules["{$key}.description"] = 'required|string|max:1000';
                    $rules["{$key}.background_image"] = 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240';
                } elseif ($key === 'core_subjects') {
                    $rules["{$key}.title"] = 'required|string|max:200';
                    $rules["{$key}.description"] = 'required|string|max:1000';
                    $rules["{$key}.items"] = 'nullable|array';
                    $rules["{$key}.items.*.title"] = 'required|string';
                    $rules["{$key}.items.*.icon"] = 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240';
                } elseif ($key === 'facilities') {
                    $rules["{$key}.title"] = 'required|string|max:200';
                    $rules["{$key}.description"] = 'required|string|max:1000';
                    $rules["{$key}.main_title"] = 'required|string|max:200';
                    $rules["{$key}.main_description"] = 'required|string|max:1000';
                    $rules["{$key}.main_image"] = 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240';
                    $rules["{$key}.items"] = 'nullable|array';
                    $rules["{$key}.items.*.name"] = 'required|string';
                    $rules["{$key}.items.*.image"] = 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240';
                } elseif ($key === 'career_paths') {
                    $rules["{$key}.title"] = 'required|string|max:200';
                    $rules["{$key}.description"] = 'required|string|max:1000';
                    $rules["{$key}.items"] = 'nullable|array';
                    $rules["{$key}.items.*.title"] = 'required|string';
                    $rules["{$key}.items.*.icon"] = 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240';
                }
            }
        }

        return $rules;
    }
}
