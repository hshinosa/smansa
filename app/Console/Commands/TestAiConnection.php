<?php

namespace App\Console\Commands;

use App\Services\GroqService;
use Illuminate\Console\Command;

class TestAiConnection extends Command
{
    protected $signature = 'ai:test-connection';
    protected $description = 'Validate AI configuration and test connectivity to Groq';

    public function handle(GroqService $groq): int
    {
        $this->info('Testing AI Configuration & Connectivity');
        $this->info('===========================================');
        $this->newLine();

        // Debug: Show current settings
        $this->info('Current Settings:');
        $apiKeys = \App\Models\AiSetting::get('groq_api_keys', '[]');
        $keys = is_array($apiKeys) ? $apiKeys : json_decode($apiKeys, true) ?? [];
        $this->line('  API Keys configured: ' . count($keys));
        $this->line('  Chat Model: ' . \App\Models\AiSetting::get('groq_chat_model', 'NOT SET'));
        $this->line('  Content Model: ' . \App\Models\AiSetting::get('groq_content_model', 'NOT SET'));
        $this->newLine();

        // Check Groq
        $this->info('Testing Groq Completion...');
        
        if (!$groq->isAvailable()) {
            $this->error('No Groq API keys configured. Add keys in Admin > AI Settings.');
            return Command::FAILURE;
        }

        $startTime = microtime(true);
        $result = $groq->chatCompletion([
            ['role' => 'user', 'content' => 'Say "Hello, system check passed!"']
        ]);
        $elapsed = round(microtime(true) - $startTime, 2);

        if ($result['success']) {
            $provider = $result['provider'] ?? 'unknown';
            $model = $result['model'] ?? 'unknown';
            $this->line("  <fg=green>OK</> Connection successful via <fg=cyan>{$provider}</> ({$model})");
            $this->line("  <fg=green>OK</> Response time: {$elapsed}s");
            $this->line("  <fg=green>OK</> AI Response: \"{$result['message']}\"");
        } else {
            $this->line("  <fg=red>FAIL</> Connection failed");
            $this->line("  <fg=red>FAIL</> Error: " . ($result['error'] ?? 'Unknown error'));
        }

        $this->newLine();
        $this->info('===========================================');
        
        return $result['success'] ? Command::SUCCESS : Command::FAILURE;
    }
}
