<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RagDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'excerpt',
        'file_path',
        'file_type',
        'file_size',
        'source',
        'category',
        'metadata',
        'is_active',
        'is_processed',
        'uploaded_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_processed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(RagDocumentChunk::class, 'document_id');
    }
}
