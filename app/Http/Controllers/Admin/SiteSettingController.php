<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteSetting;
use App\Services\ImageService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ActivityLogger;
use App\Http\Requests\SiteSettingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiteSettingController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index()
    {
        $settings = SiteSetting::with('media')->get()->keyBy('section_key');
        $sections = [];
        
        foreach (array_keys(SiteSetting::getSectionFields()) as $key) {
            $dbRow = $settings->get($key);
            $dbContent = ($dbRow && isset($dbRow['content'])) ? $dbRow['content'] : null;
            $content = SiteSetting::getContent($key, $dbContent);
            
            if ($dbRow) {
                if (str_starts_with($key, 'hero_')) {
                    $media = $this->imageService->getFirstMediaData($dbRow, $key);
                    if ($media) {
                        $content['backgroundImage'] = $media;
                    }
                }
                
                if ($key === 'general') {
                    $logoMedia = $this->imageService->getFirstMediaData($dbRow, 'site_logo');
                    if ($logoMedia) $content['siteLogoMedia'] = $logoMedia;
                    
                    $faviconMedia = $this->imageService->getFirstMediaData($dbRow, 'site_favicon');
                    if ($faviconMedia) $content['siteFaviconMedia'] = $faviconMedia;

                    $heroImageMedia = $this->imageService->getFirstMediaData($dbRow, 'hero_image');
                    if ($heroImageMedia) $content['heroImageMedia'] = $heroImageMedia;
                }
            }
            
            $sections[$key] = $content;
        }

        return Inertia::render('Admin/SiteSettings/Index', [
            'sections' => $sections,
            'activeSection' => request('section', 'general')
        ]);
    }

    public function update(SiteSettingRequest $request)
    {
        try {
            DB::beginTransaction();

            $section = $request->input('section');
            $content = $request->input('content');
            
            // Clean content from null files sent as strings or empty values
            foreach ($content as $key => $value) {
                if ($value === 'undefined' || $value === 'null') {
                    unset($content[$key]);
                }
            }

            $setting = SiteSetting::firstOrCreate(['section_key' => $section]);
            $existingContent = is_array($setting->content) ? $setting->content : [];
            
            // Re-map nested sanitization
            foreach ($content as $key => &$value) {
                if (is_string($value)) {
                    $value = strip_tags($value, '<b><i><u><p><br>');
                }
            }

            if ($section === 'general') {
                if ($request->hasFile("content.site_logo")) {
                    $setting->clearMediaCollection('site_logo');
                    $media = $setting->addMediaFromRequest("content.site_logo")->toMediaCollection('site_logo');
                    $content['site_logo'] = $media->getUrl();
                } else {
                    // Preserve existing if not re-uploaded
                    $content['site_logo'] = $existingContent['site_logo'] ?? ($content['site_logo'] ?? null);
                }

                if ($request->hasFile("content.hero_image")) {
                    $setting->clearMediaCollection('hero_image');
                    $media = $setting->addMediaFromRequest("content.hero_image")->toMediaCollection('hero_image');
                    $content['hero_image'] = $media->getUrl();
                } else {
                    // Preserve existing if not re-uploaded
                    $content['hero_image'] = $existingContent['hero_image'] ?? ($content['hero_image'] ?? null);
                }
            }

            if (str_starts_with($section, 'hero_')) {
                if ($request->hasFile("content.image_file")) {
                    $setting->clearMediaCollection($section);
                    $media = $setting->addMediaFromRequest("content.image_file")->toMediaCollection($section);
                    $content['image'] = $media->getUrl();
                } else {
                    $content['image'] = $existingContent['image'] ?? ($content['image'] ?? null);
                }
                unset($content['image_file']);
            }

            $setting->content = $content;
            $setting->save();

            SiteSetting::forgetCache();
            DB::commit();
            
            ActivityLogger::log("Update Pengaturan Situs", "Memperbarui bagian: " . $section, $request);

            return redirect()->route('admin.site-settings.index', ['section' => $section])
                ->with('success', 'Pengaturan situs berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update site settings: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memperbarui pengaturan situs.']);
        }
    }
}
