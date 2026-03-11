<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPageSetting;
use App\Services\ImageService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Helpers\ActivityLogger;
use App\Helpers\HtmlSanitizer;
use App\Http\Requests\LandingPageRequest;

class LandingPageContentController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index()
    {
        try {
            $settingsFromDb = LandingPageSetting::with('media')->get()->keyBy('section_key');
            $pageData = [];
            $sectionKeys = array_keys(LandingPageSetting::getSectionFields());

            foreach ($sectionKeys as $key) {
                $dbRow = $settingsFromDb->get($key);
                $dbContent = ($dbRow && isset($dbRow['content']) && is_array($dbRow['content'])) ? $dbRow['content'] : null;
                $content = LandingPageSetting::getContent($key, $dbContent);

                if ($dbRow) {
                    if ($key === 'hero') {
                        $bgMedia = $this->imageService->getFirstMediaData($dbRow, 'hero_background');
                        if ($bgMedia) {
                            $content['backgroundImage'] = $bgMedia;
                            $content['background_image_url'] = $bgMedia['original_url'];
                        }

                        $studentMedia = $this->imageService->getFirstMediaData($dbRow, 'hero_student');
                        if ($studentMedia) {
                            $content['studentImage'] = $studentMedia;
                            $content['student_image_url'] = $studentMedia['original_url'];
                        }
                    } elseif ($key === 'about_lp') {
                        $aboutMedia = $this->imageService->getFirstMediaData($dbRow, 'about_image');
                        if ($aboutMedia) {
                            $content['aboutImage'] = $aboutMedia;
                            $content['image_url'] = $aboutMedia['original_url'];
                        }
                    } elseif ($key === 'kepsek_welcome_lp') {
                        $kepsekMedia = $this->imageService->getFirstMediaData($dbRow, 'kepsek_image');
                        if ($kepsekMedia) {
                            $content['kepsekImage'] = $kepsekMedia;
                            $content['kepsek_image_url'] = $kepsekMedia['original_url'];
                        }
                    }
                }

                $pageData[$key] = $content;
            }

            return Inertia::render('Admin/LandingPageContentPage', [
                'currentSettings' => $pageData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in LandingPageContentController@index: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memuat data.']);
        }
    }

    public function storeOrUpdate(LandingPageRequest $request)
    {
        try {
            DB::beginTransaction();

            $inputData = $request->validated();
            $sectionKey = $inputData['section'];
            $content = $inputData['content'];

            // Sanitization
            $content = $this->sanitizeSection($sectionKey, $content);

            $setting = LandingPageSetting::firstOrCreate(['section_key' => $sectionKey]);
            $existingContent = $setting->content ?? [];
            $mergedContent = array_merge($existingContent, $content);

            // Handle Media
            if ($sectionKey === 'hero') {
                if ($request->hasFile('hero.background_image')) {
                    $setting->clearMediaCollection('hero_background');
                    $media = $setting->addMediaFromRequest('hero.background_image')->toMediaCollection('hero_background');
                    $mergedContent['background_image_url'] = $media->getUrl();
                }
                if ($request->hasFile('hero.student_image')) {
                    $setting->clearMediaCollection('hero_student');
                    $media = $setting->addMediaFromRequest('hero.student_image')->toMediaCollection('hero_student');
                    $mergedContent['student_image_url'] = $media->getUrl();
                }
            } elseif ($sectionKey === 'about_lp' && $request->hasFile('about_lp.image')) {
                $setting->clearMediaCollection('about_image');
                $media = $setting->addMediaFromRequest('about_lp.image')->toMediaCollection('about_image');
                $mergedContent['image_url'] = $media->getUrl();
            } elseif ($sectionKey === 'kepsek_welcome_lp' && $request->hasFile('kepsek_welcome_lp.kepsek_image')) {
                $setting->clearMediaCollection('kepsek_image');
                $media = $setting->addMediaFromRequest('kepsek_welcome_lp.kepsek_image')->toMediaCollection('kepsek_image');
                $mergedContent['kepsek_image_url'] = $media->getUrl();
            }

            $setting->content = $mergedContent;
            $setting->save();

            DB::commit();
            ActivityLogger::log('Update Konten Landing Page', 'Bagian ' . $sectionKey . ' telah diperbarui.', $request);

            return back()->with('success', 'Konten berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating Landing Page: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memperbarui konten.']);
        }
    }

    private function sanitizeSection(string $key, array $content): array
    {
        $htmlFields = ['description_html', 'welcome_text_html'];
        $sanitized = [];
        foreach ($content as $field => $value) {
            if (in_array($field, $htmlFields)) {
                $sanitized[$field] = HtmlSanitizer::sanitize($value);
            } else {
                $sanitized[$field] = is_string($value) ? strip_tags($value) : $value;
            }
        }
        return $sanitized;
    }
}
