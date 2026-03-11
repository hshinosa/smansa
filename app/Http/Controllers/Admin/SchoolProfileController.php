<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\HtmlSanitizer;
use App\Http\Controllers\Controller;
use App\Models\SchoolProfileSetting;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Http\Requests\SchoolProfileRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchoolProfileController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index()
    {
        $settings = SchoolProfileSetting::with('media')->get()->keyBy('section_key');
        $sections = [];
        foreach (array_keys(SchoolProfileSetting::getSectionFields()) as $key) {
            $dbRow = $settings->get($key);
            $dbContent = ($dbRow && $dbRow->content) ? $dbRow->content : null;
            $content = SchoolProfileSetting::getContent($key, $dbContent);
            
            if ($dbRow) {
                $media = $this->imageService->getFirstMediaData($dbRow, $key);
                if ($media) {
                    $content['backgroundImage'] = $media;
                }
                
                if ($key === 'facilities' && isset($content['items']) && is_array($content['items'])) {
                    foreach ($content['items'] as $index => &$item) {
                        $itemMedia = $this->imageService->getFirstMediaData($dbRow, "facilities_item_{$index}");
                        if ($itemMedia) {
                            $item['image'] = $itemMedia;
                            $item['image_url'] = $itemMedia['original_url'];
                        }
                    }
                }
            }
            
            $sections[$key] = $content;
        }

        return Inertia::render('Admin/SchoolProfile/Index', [
            'sections' => $sections,
            'activeSection' => request('section', 'history')
        ]);
    }

    public function update(SchoolProfileRequest $request)
    {
        try {
            DB::beginTransaction();

            $sectionKey = $request->input('section');
            $content = $request->input('content');
            
            $setting = SchoolProfileSetting::firstOrCreate(['section_key' => $sectionKey]);
            $existingContent = $setting->content ?? [];

            $files = $request->file('content');
            
            if ($files) {
                foreach ($files as $field => $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        if ($field === 'image_url' || $field === 'image') {
                            $setting->clearMediaCollection($sectionKey);
                            $media = $setting->addMedia($file)->toMediaCollection($sectionKey);
                            $content['image_url'] = $media->getUrl();
                            if (isset($content['image'])) unset($content['image']);
                        }
                    }
                }

                if ($sectionKey === 'facilities' && isset($files['items']) && is_array($files['items'])) {
                    foreach ($files['items'] as $index => $itemFile) {
                        if (isset($itemFile['image_file']) && $itemFile['image_file'] instanceof \Illuminate\Http\UploadedFile) {
                            $collectionName = "facilities_item_{$index}";
                            $setting->clearMediaCollection($collectionName);
                            $media = $setting->addMedia($itemFile['image_file'])->toMediaCollection($collectionName);
                            if (isset($content['items'][$index])) {
                                $content['items'][$index]['image_url'] = $media->getUrl();
                            }
                        }
                    }
                }
            }

            if (isset($existingContent['image_url']) && !isset($content['image_url']) && !isset($content['image'])) {
                $content['image_url'] = $existingContent['image_url'];
            }

            if ($sectionKey === 'facilities' && isset($existingContent['items']) && isset($content['items'])) {
                foreach ($content['items'] as $index => $item) {
                    if (!isset($item['image_url']) && isset($existingContent['items'][$index]['image_url'])) {
                        $content['items'][$index]['image_url'] = $existingContent['items'][$index]['image_url'];
                    }
                }
            }

            $setting->content = HtmlSanitizer::sanitizeSection($sectionKey, $content);
            $setting->save();

            DB::commit();
            return redirect()->route('admin.school-profile.index', ['section' => $sectionKey])
                ->with('success', 'Konten profil berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update school profile: ' . $e->getMessage());
            return redirect()->back()->withErrors(['general' => 'Gagal memperbarui profil sekolah.']);
        }
    }
}
