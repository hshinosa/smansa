<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    protected $fillable = [
        'session_id',
        'ip_address',
        'user_agent',
        'sender',
        'message',
        'metadata',
        'is_rag_enhanced',
        'retrieved_documents',
    ];

    protected $casts = [
        'metadata' => 'array',
        'retrieved_documents' => 'array',
        'is_rag_enhanced' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
