<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class InstagramBotAccount extends Model
{
    /**
     * The table associated with the model.
     * Using 'sc_' prefix for scraper-related tables
     */
    protected $table = 'sc_bot_accounts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'username',
        'password',
        'is_active',
        'last_used_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the password attribute (decrypt for use)
     * Note: Password is stored plain for Python scraper compatibility
     * In production, consider using Laravel's encryption if Python can decrypt
     */
    public function getPasswordAttribute($value)
    {
        // Return as-is for now (Python scraper needs plain text)
        // TODO: Implement encryption/decryption if needed
        return $value;
    }

    /**
     * Set the password attribute
     */
    public function setPasswordAttribute($value)
    {
        // Store as-is for Python scraper compatibility
        $this->attributes['password'] = $value;
    }

    /**
     * Scope to get only active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get masked password for display (show only first 2 and last 2 chars)
     */
    public function getMaskedPasswordAttribute()
    {
        if (empty($this->attributes['password'])) {
            return '••••••••';
        }

        $pass = $this->attributes['password'];
        $length = strlen($pass);

        if ($length <= 4) {
            return str_repeat('•', $length);
        }

        return substr($pass, 0, 2) . str_repeat('•', $length - 4) . substr($pass, -2);
    }

    /**
     * Check if account credentials are configured (not placeholder)
     */
    public function isConfigured(): bool
    {
        return $this->username !== 'CHANGE_ME' && 
               $this->password !== 'CHANGE_ME';
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute(): string
    {
        if (!$this->isConfigured()) {
            return 'warning'; // Orange - needs configuration
        }

        return $this->is_active ? 'success' : 'danger';
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->isConfigured()) {
            return 'Not Configured';
        }

        return $this->is_active ? 'Active' : 'Inactive';
    }
}
