<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Seragam;
use Illuminate\Http\Request;

class SeragamController extends Controller
{
    /**
     * Display a listing of seragam (uniforms) for public viewing.
     */
    public function index()
    {
        $seragams = Seragam::where('is_active', true)
            ->ordered()
            ->get()
            ->map(function ($seragam) {
                return [
                    'id' => $seragam->id,
                    'name' => $seragam->name,
                    'slug' => $seragam->slug,
                    'category' => $seragam->category,
                    'description' => $seragam->description,
                    'usage_days' => $seragam->usage_days,
                    'rules' => $seragam->rules,
                    'image_url' => $seragam->image_url,
                ];
            });

        return inertia('SeragamPage', [
            'seragams' => $seragams,
        ]);
    }
}
