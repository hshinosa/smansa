<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_id' => 'required|string',
            'rating' => 'required|in:helpful,not_helpful',
            'feedback' => 'nullable|string|max:1000',
        ];
    }
}
