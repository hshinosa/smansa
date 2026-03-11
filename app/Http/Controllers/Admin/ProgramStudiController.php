<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramStudiSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Http\Requests\ProgramStudiRequest;
use Illuminate\Support\Facades\DB;

class ProgramStudiController extends Controller
{
    public function index(Request $request)
    {
        $programName = $request->query("program", "mipa");
        
        if (!in_array($programName, ["mipa", "ips", "bahasa"])) {
            return redirect()->route("admin.program-studi.index", ["program" => "mipa"]);
        }

        try {
            $settingsFromDb = ProgramStudiSetting::with('media')->where("program_name", $programName)
                ->get()
                ->keyBy("section_key");
            
            $heroSetting = $settingsFromDb->get('hero');
            $thumbnailCardUrl = $heroSetting ? $heroSetting->getFirstMediaUrl('thumbnail_card') : null;
            
            $pageData = [];
            $sectionKeys = array_keys(ProgramStudiSetting::getSectionFields());

            foreach ($sectionKeys as $key) {
                $dbRow = $settingsFromDb->get($key);
                $dbContent = ($dbRow && isset($dbRow["content"]) && is_array($dbRow["content"])) ? $dbRow["content"] : null;
                $pageData[$key] = ProgramStudiSetting::getContent($key, $dbContent);

                if ($dbRow) {
                    if ($key === 'hero') {
                        $mediaUrl = $dbRow->getFirstMediaUrl('hero_background_image');
                        if ($mediaUrl) $pageData[$key]['background_image'] = $mediaUrl;
                    }
                    if ($key === 'facilities') {
                        $mediaUrl = $dbRow->getFirstMediaUrl('facilities_main_image');
                        if ($mediaUrl) $pageData[$key]['main_image'] = $mediaUrl;
                    }
                    if (isset($pageData[$key]['items']) && is_array($pageData[$key]['items'])) {
                        foreach ($pageData[$key]['items'] as $index => &$item) {
                            if ($key === 'core_subjects') {
                                $mediaUrl = $dbRow->getFirstMediaUrl("core_subjects_item_{$index}_icon");
                                if ($mediaUrl) $item['icon'] = $mediaUrl;
                            }
                            if ($key === 'facilities') {
                                $mediaUrl = $dbRow->getFirstMediaUrl("facilities_item_{$index}_image");
                                if ($mediaUrl) $item['image'] = $mediaUrl;
                            }
                            if ($key === 'career_paths') {
                                $mediaUrl = $dbRow->getFirstMediaUrl("career_paths_item_{$index}_icon");
                                if ($mediaUrl) $item['icon'] = $mediaUrl;
                            }
                        }
                    }
                }
            }

            return Inertia::render("Admin/ProgramStudi/Index", [
                "currentSettings" => $pageData,
                "activeProgram" => $programName,
                "thumbnailCardUrl" => $thumbnailCardUrl,
            ]);
        } catch (\Exception $e) {
            Log::error("Error in ProgramStudiController@index: " . $e->getMessage());
            return back()->withErrors(["general" => "Gagal memuat data."]);
        }
    }

    public function storeOrUpdate(ProgramStudiRequest $request)
    {
        try {
            DB::beginTransaction();

            $programName = $request->input("program_name");
            
            if ($request->hasFile('thumbnail_card')) {
                $heroSetting = ProgramStudiSetting::firstOrCreate(['program_name' => $programName, 'section_key' => 'hero']);
                $heroSetting->clearMediaCollection('thumbnail_card');
                $heroSetting->addMediaFromRequest('thumbnail_card')->toMediaCollection('thumbnail_card');
            }

            $sectionFields = ProgramStudiSetting::getSectionFields();
            
            foreach ($sectionFields as $sectionKey => $fields) {
                if (!$request->has($sectionKey)) continue;

                $content = $request->input($sectionKey);
                $setting = ProgramStudiSetting::firstOrCreate(['program_name' => $programName, 'section_key' => $sectionKey]);
                
                // Sanitization
                foreach ($content as $fKey => &$fVal) {
                    if (is_string($fVal)) {
                        $fVal = in_array($fKey, ['description', 'main_description', 'quote']) 
                            ? strip_tags($fVal, '<b><i><u><p><br>') 
                            : strip_tags($fVal);
                    }
                }

                // Top-level images
                foreach ($fields as $fieldKey => $fieldType) {
                    if ($fieldType === "image" && $request->hasFile("{$sectionKey}.{$fieldKey}")) {
                        $collection = $sectionKey . '_' . $fieldKey;
                        $setting->clearMediaCollection($collection);
                        $media = $setting->addMediaFromRequest("{$sectionKey}.{$fieldKey}")->toMediaCollection($collection);
                        $content[$fieldKey] = $media->getUrl();
                    }
                }

                // List items with images/icons
                if (isset($fields["items"]) && $fields["items"] === "list" && isset($content["items"])) {
                    foreach ($content["items"] as $index => &$item) {
                        $itemFields = ['icon', 'image'];
                        foreach ($itemFields as $if) {
                            if ($request->hasFile("{$sectionKey}.items.{$index}.{$if}")) {
                                $collection = "{$sectionKey}_item_{$index}_{$if}";
                                $setting->clearMediaCollection($collection);
                                $media = $setting->addMediaFromRequest("{$sectionKey}.items.{$index}.{$if}")->toMediaCollection($collection);
                                $item[$if] = $media->getUrl();
                            }
                        }
                    }
                }

                $setting->content = $content;
                $setting->save();
            }

            DB::commit();
            return back()->with("success", "Data berhasil disimpan.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating Program Studi: " . $e->getMessage());
            return back()->withErrors(["general" => "Gagal menyimpan data."]);
        }
    }
}
