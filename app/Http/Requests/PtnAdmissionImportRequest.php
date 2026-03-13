<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PtnAdmissionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->guard()->check();
    }

    public function rules(): array
    {
        return [
            'file' => 'required|mimes:xlsx,xls,csv,pdf|max:5120',
        ];
    }
}
