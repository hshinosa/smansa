<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AiSetting;

class PopulateAiSettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            // Groq API Keys (JSON array of keys for round-robin)
            [
                'key' => 'groq_api_keys',
                'value' => json_encode([]),
                'type' => 'json',
            ],
            
            // Groq Chat Model (for chatbot/RAG)
            [
                'key' => 'groq_chat_model',
                'value' => 'llama-3.3-70b-versatile',
                'type' => 'string',
            ],

            // Groq Content Model (for news article generation)
            [
                'key' => 'groq_content_model',
                'value' => 'llama-3.3-70b-versatile',
                'type' => 'string',
            ],
            
            // General AI Settings
            [
                'key' => 'ai_max_tokens',
                'value' => '2000',
                'type' => 'integer',
            ],
            [
                'key' => 'ai_temperature',
                'value' => '0.7',
                'type' => 'string',
            ],
            
            // RAG Configuration
            [
                'key' => 'rag_enabled',
                'value' => '1',
                'type' => 'boolean',
            ],
            [
                'key' => 'rag_top_k',
                'value' => '5',
                'type' => 'integer',
            ],
            [
                'key' => 'queue_alert_pending_threshold',
                'value' => '100',
                'type' => 'integer',
            ],
            [
                'key' => 'queue_alert_failed_threshold',
                'value' => '1',
                'type' => 'integer',
            ],

            // Self-hosted Embedding Configuration (Docker TEI)
            [
                'key' => 'embedding_provider',
                'value' => 'tei',
                'type' => 'string',
            ],
            [
                'key' => 'embedding_base_url',
                'value' => env('EMBEDDING_BASE_URL', 'http://embedding:8080'),
                'type' => 'string',
            ],
            [
                'key' => 'embedding_model',
                'value' => env('EMBEDDING_MODEL', 'intfloat/multilingual-e5-small'),
                'type' => 'string',
            ],
            [
                'key' => 'embedding_dimensions',
                'value' => env('EMBEDDING_DIMENSIONS', '384'),
                'type' => 'integer',
            ],

            
            // System Prompt
            [
                'key' => 'system_prompt',
                'value' => "Anda adalah AI SMANSA. Jawab singkat dan jelas, maksimal 3-4 kalimat.",
                'type' => 'string',
            ]
        ];

        foreach ($settings as $setting) {
            AiSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Clean up old settings that are no longer needed
        AiSetting::whereIn('key', [
            'ai_primary_provider',
            'ai_model_base_url',
            'ai_model_api_key',
            'ai_model_name',
            'ai_embedding_model',
            'use_ollama_fallback',
            'ollama_base_url',
            'ollama_model',
            'ollama_embedding_model',
        ])->delete();
        
        $this->command->info('AI Settings populated (Groq configuration).');
    }
}
