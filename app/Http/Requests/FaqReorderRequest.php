<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FaqReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->guard()->check();
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array',
        ];
    }
}
