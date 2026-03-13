<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:255',
            'stream' => 'nullable|boolean',
            'use_cache' => 'nullable|boolean',
        ];
    }
}
