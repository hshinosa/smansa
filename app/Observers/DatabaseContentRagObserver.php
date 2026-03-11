<?php

namespace App\Observers;

use App\Services\DatabaseContentRagSyncService;
use Illuminate\Database\Eloquent\Model;

class DatabaseContentRagObserver
{
    public function __construct(protected DatabaseContentRagSyncService $syncService)
    {
    }

    public function saved(Model $model): void
    {
        $this->syncService->syncModel($model, true);
    }

    public function deleted(Model $model): void
    {
        $this->syncService->removeModel($model);
    }
}
