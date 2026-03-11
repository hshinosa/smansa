<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RagDocumentChunk extends Model
{
    protected $fillable = [
        'document_id',
        'content',
        'chunk_index',
        'token_count',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(RagDocument::class, 'document_id');
    }
}
