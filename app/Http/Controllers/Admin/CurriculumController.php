<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurriculumSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

use App\Http\Requests\CurriculumUpdateRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CurriculumController extends Controller
{
    public function index()
    {
        $settings = CurriculumSetting::all()->keyBy('section_key');
        $sections = [];
        $mediaCollections = CurriculumSetting::getMediaCollections();
        $imageService = new \App\Services\ImageService();

        foreach (array_keys(CurriculumSetting::getSectionFields()) as $key) {
            $dbRow = $settings->get($key);
            $dbContent = ($dbRow && isset($dbRow['content'])) ? $dbRow['content'] : null;
            $content = CurriculumSetting::getContent($key, $dbContent);

            if ($dbRow && isset($mediaCollections[$key])) {
                $media = $imageService->getFirstMediaData($dbRow, $mediaCollections[$key]);
                if ($media) {
                    $content['image'] = $media;
                }
            }

            $sections[$key] = $content;
        }

        return Inertia::render('Admin/Curriculum/Index', [
            'settings' => $sections,
            'sectionMeta' => CurriculumSetting::getSectionMeta(),
            'activeSection' => request('section', 'hero')
        ]);
    }

    public function update(CurriculumUpdateRequest $request)
    {
        $section = $request->validated('section');
        $content = $request->validated('content');

        try {
            DB::beginTransaction();

            $setting = CurriculumSetting::firstOrCreate(['section_key' => $section]);

            $mediaCollections = CurriculumSetting::getMediaCollections();
            $collection = $mediaCollections[$section] ?? null;

            if ($collection && $request->hasFile('media')) {
                $setting->clearMediaCollection($collection);
                $setting->addMediaFromRequest('media')->toMediaCollection($collection);
            }

            // Clean content to prevent slop and maintain structure
            $setting->content = $this->sanitizeContent($content);
            $setting->save();

            DB::commit();

            return back()->with('success', 'Konten kurikulum berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update curriculum section {$section}: " . $e->getMessage());

            return back()->withErrors(['general' => 'Gagal memperbarui konten. Silakan coba lagi.']);
        }
    }

    private function sanitizeContent(array $content): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->sanitizeContent($value);
            }
            
            if (is_string($value)) {
                // Remove potential script tags, keep basic formatting if needed
                // For now, full strip to be safe, or use HTMLPurifier if rich text is needed
                return strip_tags($value);
            }

            return $value;
        }, $content);
    }
}
