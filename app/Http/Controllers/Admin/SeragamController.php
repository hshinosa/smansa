<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeragamRequest;
use App\Models\Seragam;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeragamController extends Controller
{
    /**
     * Display a listing of the seragam (uniforms).
     */
    public function index(Request $request)
    {
        $query = Seragam::query()->orderBy('sort_order');

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        $seragams = $query->get()->map(function ($seragam) {
            return [
                'id' => $seragam->id,
                'name' => $seragam->name,
                'slug' => $seragam->slug,
                'category' => $seragam->category,
                'description' => $seragam->description,
                'usage_days' => $seragam->usage_days,
                'rules' => $seragam->rules,
                'sort_order' => $seragam->sort_order,
                'is_active' => $seragam->is_active,
                'image_url' => $seragam->image_url,
            ];
        });

        return inertia('Admin/SeragamPage', [
            'seragams' => $seragams,
        ]);
    }

    /**
     * Store a newly created seragam.
     */
    public function store(SeragamRequest $request)
    {
        $validated = $request->validated();
        
        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Seragam::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Set sort order to be last if not provided
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = Seragam::max('sort_order') + 1;
        }

        $seragam = Seragam::create($validated);

        // Handle image upload
        if ($request->hasFile('image')) {
            $seragam->addMediaFromRequest('image')->toMediaCollection('images');
        }

        return redirect()->route('admin.seragam.index')
            ->with('success', 'Seragam berhasil ditambahkan');
    }

    /**
     * Update the specified seragam.
     */
    public function update(SeragamRequest $request, Seragam $seragam)
    {
        $validated = $request->validated();

        // Handle slug uniqueness
        if (isset($validated['slug']) && $validated['slug'] !== $seragam->slug) {
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Seragam::where('slug', $validated['slug'])->where('id', '!=', $seragam->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $seragam->update($validated);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Remove old image
            $seragam->clearMediaCollection('images');
            $seragam->addMediaFromRequest('image')->toMediaCollection('images');
        }

        return redirect()->route('admin.seragam.index')
            ->with('success', 'Seragam berhasil diperbarui');
    }

    /**
     * Remove the specified seragam.
     */
    public function destroy(Seragam $seragam)
    {
        // Delete associated media first
        $seragam->clearMediaCollection('images');
        
        $seragam->delete();

        return redirect()->route('admin.seragam.index')
            ->with('success', 'Seragam berhasil dihapus');
    }
}
