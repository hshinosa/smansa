<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use App\Services\GroqService;
use App\Services\EmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

use App\Http\Requests\AiSettingRequest;
use Illuminate\Support\Facades\DB;

class AiSettingController extends Controller
{
    public function index(EmbeddingService $embeddingService)
    {
        $settings = AiSetting::all()->groupBy('key')->map(fn($group) => $group->first());

        // Get Apify token from settings table
        $apifyToken = DB::table('settings')
            ->where('key', 'apify_api_token')
            ->value('value');

        $queuePendingJobs = DB::table('jobs')->count();
        $queueFailedJobs = DB::table('failed_jobs')->count();

        $pendingThreshold = (int) AiSetting::get('queue_alert_pending_threshold', 100);
        $failedThreshold = (int) AiSetting::get('queue_alert_failed_threshold', 1);

        $pendingAlert = $queuePendingJobs >= $pendingThreshold;
        $failedAlert = $queueFailedJobs >= $failedThreshold;

        return Inertia::render('Admin/AiSettings/Index', [
            'settings' => $settings->values(),
            'apifyToken' => $apifyToken,
            'embeddingHealth' => [
                'available' => $embeddingService->isAvailable(),
                'provider' => $embeddingService->getProvider(),
                'dimensions' => $embeddingService->getDimensions(),
                'base_url' => AiSetting::get('embedding_base_url', env('EMBEDDING_BASE_URL', 'http://embedding:8080')),
                'model' => AiSetting::get('embedding_model', env('EMBEDDING_MODEL', 'intfloat/multilingual-e5-small')),
            ],
            'queueHealth' => [
                'pending_jobs' => $queuePendingJobs,
                'failed_jobs' => $queueFailedJobs,
                'pending_threshold' => $pendingThreshold,
                'failed_threshold' => $failedThreshold,
                'pending_alert' => $pendingAlert,
                'failed_alert' => $failedAlert,
                'status' => ($pendingAlert || $failedAlert) ? 'alert' : 'ok',
            ],
        ]);
    }

    public function models(GroqService $groqService)
    {
        $models = [];

        try {
            $groqModels = $groqService->getAvailableModels();
            foreach ($groqModels as $model) {
                $models[] = [
                    'id' => $model['id'] ?? $model['name'] ?? 'unknown',
                    'object' => 'model',
                    'created' => $model['created'] ?? time(),
                    'owned_by' => $model['owned_by'] ?? 'groq',
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Groq models fetch failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'models' => $models,
        ]);
    }

    public function update(AiSettingRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            foreach ($validated['settings'] as $setting) {
                $existingSetting = AiSetting::where('key', $setting['key'])->first();
                $type = $existingSetting ? $existingSetting->type : 'string';
                $value = $setting['value'];
                
                // For groq_api_keys, store as JSON (skip strip_tags)
                if ($setting['key'] === 'groq_api_keys') {
                    $type = 'json';
                    AiSetting::set($setting['key'], $value, $type);
                    continue;
                }
                
                if (is_string($value)) {
                    $value = strip_tags($value);
                }
                
                AiSetting::set($setting['key'], $value, $type);
            }

            DB::commit();
            return redirect()->route('admin.ai-settings.index')
                            ->with('success', 'Pengaturan AI berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update AI settings: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memperbarui pengaturan AI.']);
        }
    }

    /**
     * Update Apify API token
     */
    public function updateApifyToken(Request $request)
    {
        $request->validate([
            'apify_token' => 'required|string|min:20',
        ]);

        try {
            DB::table('settings')->updateOrInsert(
                ['key' => 'apify_api_token'],
                [
                    'value' => $request->apify_token,
                    'updated_at' => now(),
                ]
            );

            Log::info('[AI Settings] Apify API token updated');

            return redirect()->route('admin.ai-settings.index', ['tab' => 'apify'])
                ->with('success', 'API key berhasil disimpan');
        } catch (\Exception $e) {
            Log::error('Failed to update Apify token: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal menyimpan API key.']);
        }
    }
}
