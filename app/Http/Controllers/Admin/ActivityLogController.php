<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        // Fetch latest logs, can be paginated
        $logs = ActivityLog::with('causer') // Load polymorphic relation
            ->latest()
            ->paginate($request->input('per_page', 10)); // Default 10 per page

        return response()->json($logs);
    }
}
