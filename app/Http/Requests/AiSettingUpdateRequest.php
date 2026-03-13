<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->guard()->check();
    }

    public function rules(): array
    {
        return [
            'key' => 'required|string',
            'value' => 'required',
        ];
    }
}
