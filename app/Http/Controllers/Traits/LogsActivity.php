<?php

namespace App\Http\Controllers\Traits;

use App\Services\ActivityLogService;

trait LogsActivity
{
    protected function logCreated(string $module, $model, ?string $description = null)
    {
        ActivityLogService::log(
            action: 'create',
            module: $module,
            description: $description ?? "Created {$module}",
            subject: $model
        );
    }

    protected function logUpdated(string $module, $model, ?string $description = null)
    {
        ActivityLogService::log(
            action: 'update',
            module: $module,
            description: $description ?? "Updated {$module}",
            subject: $model
        );
    }

    protected function logDeleted(string $module, $model, ?string $description = null)
    {
        ActivityLogService::log(
            action: 'delete',
            module: $module,
            description: $description ?? "Deleted {$module}",
            subject: $model
        );
    }

    protected function logViewed(string $module, $model, ?string $description = null)
    {
        ActivityLogService::log(
            action: 'view',
            module: $module,
            description: $description ?? "Viewed {$module}",
            subject: $model
        );
    }

    protected function logAction(string $action, string $module, $model, string $description)
    {
        ActivityLogService::log(
            action: $action,
            module: $module,
            description: $description,
            subject: $model
        );
    }
}
