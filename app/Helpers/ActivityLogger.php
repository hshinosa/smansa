<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(string $action, ?string $description = null, ?Request $request = null)
    {
        $user = Auth::guard('admin')->user();
        
        ActivityLog::create([
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user ? $user->id : null,
            'description' => $action . ($description ? ': ' . $description : ''),
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
        ]);
    }
}
