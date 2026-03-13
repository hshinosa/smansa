<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\RedirectResponse;

class AiSettingActionController extends Controller
{
    public function reindexDbContent(): RedirectResponse
    {
        Artisan::queue('rag:reindex-db-content');

        return back()->with('success', 'Reindex database content dimulai di queue.');
    }
}
