<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'details',
        'causer_type',
        'causer_id',
        'ip_address',
        'user_agent',
    ];

    public function causer()
    {
        return $this->morphTo();
    }
    
    // Alias for backward compatibility if needed, but better to use causer
    public function admin()
    {
        return $this->morphTo(__FUNCTION__, 'causer_type', 'causer_id');
    }
}
