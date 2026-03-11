<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RagDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => ($this->isMethod('POST') && !$this->hasFile('file') ? 'required' : 'nullable') . '|string',
            'excerpt' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'file' => 'nullable|file|mimes:pdf,txt,doc,docx|max:10240',
            'is_active' => 'boolean',
        ];
    }
}
