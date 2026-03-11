<?php

namespace App\Console\Commands;

use App\Services\DatabaseContentRagSyncService;
use Illuminate\Console\Command;

class RagReindexDbContent extends Command
{
    protected $signature = 'rag:reindex-db-content {--table= : One table key from sync map} {--sync : Process chunks inline (without queue)}';
    protected $description = 'Reindex database content into RAG documents and queue chunk processing';

    public function handle(DatabaseContentRagSyncService $syncService): int
    {
        $table = $this->option('table');
        $inline = (bool) $this->option('sync');

        if ($table && !array_key_exists($table, DatabaseContentRagSyncService::MODEL_MAP)) {
            $this->error('Unknown table key: ' . $table);
            $this->line('Allowed: ' . implode(', ', array_keys(DatabaseContentRagSyncService::MODEL_MAP)));
            return self::FAILURE;
        }

        $this->info('Starting DB content reindex...');
        $this->line('Mode: ' . ($inline ? 'inline processing' : 'queue processing'));
        if ($table) {
            $this->line('Table: ' . $table);
        }

        $result = $syncService->reindexAll($table, !$inline, $inline);

        $this->newLine();
        $this->info('Reindex completed.');
        $this->line('Total scanned: ' . $result['total']);
        $this->line('Synced: ' . $result['synced']);
        $this->line('Skipped: ' . $result['skipped']);

        return self::SUCCESS;
    }
}
