<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PtnAdmissionBulkStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->guard()->check();
    }

    public function rules(): array
    {
        return [
            'batch_id' => 'required|exists:ptn_admission_batches,id',
            'admissions' => 'required|array',
            'admissions.*.name' => 'required|string',
        ];
    }
}
