<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:100',
            'settings.*.value' => 'nullable|string',
        ];
    }
}
