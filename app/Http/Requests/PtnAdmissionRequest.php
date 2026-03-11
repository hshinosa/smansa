<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PtnAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        if ($this->isMethod('POST') || $this->isMethod('PUT')) {
            if ($this->routeIs('admin.ptn-admissions.store-batch') || $this->routeIs('admin.ptn-admissions.update-batch')) {
                return [
                    'name' => 'required|string|max:255',
                    'type' => 'required|in:SNBP,SNBT,Mandiri',
                    'year' => 'required|integer|min:2000|max:2100',
                    'description' => 'nullable|string',
                    'is_published' => 'boolean',
                ];
            }

            if ($this->routeIs('admin.ptn-admissions.store-admission') || $this->routeIs('admin.ptn-admissions.update-admission')) {
                return [
                    'university_id' => 'required|exists:ptn_universities,id',
                    'program_studi' => 'required|string|max:255',
                    'count' => 'required|integer|min:1',
                ];
            }

            if ($this->routeIs('admin.ptn-admissions.store-university') || $this->routeIs('admin.ptn-admissions.update-university')) {
                return [
                    'name' => 'required|string|max:255',
                    'short_name' => 'nullable|string|max:50',
                    'color' => 'nullable|string|max:7',
                    'is_active' => 'boolean',
                    'sort_order' => 'nullable|integer',
                ];
            }
        }

        return [];
    }
}
