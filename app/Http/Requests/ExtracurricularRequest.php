<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtracurricularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:organisasi,ekstrakurikuler',
            'category' => 'required|string|max:255',
            'description' => 'required|string',
            'activity_description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'bg_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'icon_name' => 'nullable|string|max:255',
            'achievements_data' => 'nullable|array',
            'achievements_data.*.title' => 'required|string',
            'achievements_data.*.level' => 'nullable|string',
            'achievements_data.*.year' => 'nullable|string',
            'training_info' => 'nullable|array',
            'training_info.days' => 'nullable|array',
            'training_info.days.*' => 'string',
            'training_info.start_time' => 'nullable|string',
            'training_info.end_time' => 'nullable|string',
            'training_info.location' => 'nullable|string',
            'training_info.coach' => 'nullable|string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
