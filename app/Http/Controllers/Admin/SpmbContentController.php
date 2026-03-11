<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ActivityLogger;
use App\Helpers\HtmlSanitizer;
use App\Http\Controllers\Controller;
use App\Models\SpmbSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Http\Requests\SpmbRequest;
use Illuminate\Support\Facades\DB;

class SpmbContentController extends Controller
{
    public function index()
    {
        $settingsFromDb = SpmbSetting::all()->keyBy('section_key');
        $pageData = [];
        $sectionKeys = array_keys(SpmbSetting::getSectionFields());

        foreach ($sectionKeys as $key) {
            $dbRow = $settingsFromDb->get($key);
            $dbContent = ($dbRow && isset($dbRow['content']) && is_array($dbRow['content'])) ? $dbRow['content'] : null;
            $pageData[$key] = SpmbSetting::getContent($key, $dbContent);

            if ($key === 'pengaturan_umum' && $dbRow) {
                $mediaUrl = $dbRow->getFirstMediaUrl('banner_image');
                if ($mediaUrl) {
                    $pageData[$key]['banner_image_url'] = $mediaUrl;
                }
            }
        }

        return Inertia::render('Admin/SpmbContentPage', [
            'currentSettings' => $pageData,
            'activeSection' => request('section', 'pengaturan_umum'),
        ]);
    }

    public function storeOrUpdate(SpmbRequest $request)
    {
        try {
            DB::beginTransaction();

            $inputData = $request->all();
            $targetSection = 'pengaturan_umum';
            if (isset($inputData['pengaturan_umum'])) $targetSection = 'pengaturan_umum';
            elseif (isset($inputData['jalur_pendaftaran'])) $targetSection = 'jalur_pendaftaran';
            elseif (isset($inputData['jadwal_penting'])) $targetSection = 'jadwal_penting';
            elseif (isset($inputData['persyaratan'])) $targetSection = 'persyaratan';
            elseif (isset($inputData['prosedur'])) $targetSection = 'prosedur';
            elseif (isset($inputData['faq'])) $targetSection = 'faq';

            $sectionFields = SpmbSetting::getSectionFields();
            $finalDataToSave = [];

            foreach ($sectionFields as $sectionKey => $fields) {
                if (isset($inputData[$sectionKey])) {
                    $currentData = $inputData[$sectionKey];
                    
                    // Handle boolean
                    if ($sectionKey === 'pengaturan_umum' && isset($currentData['is_registration_open'])) {
                        $currentData['is_registration_open'] = filter_var($currentData['is_registration_open'], FILTER_VALIDATE_BOOLEAN);
                    }

                    // Handle JSON items if sent as string from FormData
                    if (isset($currentData['items']) && is_string($currentData['items'])) {
                        $currentData['items'] = json_decode($currentData['items'], true) ?: [];
                    }

                    $dataForThisSection = [];
                    foreach ($fields as $field) {
                        if (isset($currentData[$field])) {
                            $dataForThisSection[$field] = $currentData[$field];
                        } else {
                            $defaults = SpmbSetting::getDefaults($sectionKey);
                            $dataForThisSection[$field] = $defaults[$field] ?? null;
                        }
                    }

                    $finalDataToSave[$sectionKey] = HtmlSanitizer::sanitizeSection($sectionKey, $dataForThisSection);
                }
            }

            foreach ($finalDataToSave as $sectionKey => $content) {
                $setting = SpmbSetting::firstOrCreate(['section_key' => $sectionKey]);
                
                if ($sectionKey === 'pengaturan_umum' && $request->hasFile("{$sectionKey}.banner_image_file")) {
                    $setting->clearMediaCollection('banner_image');
                    $setting->addMediaFromRequest("{$sectionKey}.banner_image_file")->toMediaCollection('banner_image');
                    $content['banner_image_url'] = $setting->getFirstMediaUrl('banner_image');
                }

                $setting->content = $content;
                $setting->save();
            }

            DB::commit();
            ActivityLogger::log('Update Konten SPMB', 'Bagian ' . $targetSection . ' telah diperbarui.', $request);

            return redirect()->route('admin.spmb.index', ['section' => $targetSection])
                ->with('success', 'Konten SPMB berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating SPMB content: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memperbarui konten: ' . $e->getMessage()])->withInput();
        }
    }
}
