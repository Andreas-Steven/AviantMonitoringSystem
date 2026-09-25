<?php

namespace App\Domains\Audit\Services;

use App\Domains\Audit\Models\SystemChangeLog;
use Illuminate\Database\Eloquent\Model;

class SystemChangeLogService
{
    public function logCreated(Model $model, ?string $notes = null): void
    {
        $this->log(
            tableName: $model->getTable(),
            recordPk: (string) $model->getKey(),
            actionTypeCode: 'INSERT',
            oldData: null,
            newData: $model->getAttributes(),
            notes: $notes
        );
    }

    public function logUpdated(Model $model, array $oldData, ?string $notes = null): void
    {
        $this->log(
            tableName: $model->getTable(),
            recordPk: (string) $model->getKey(),
            actionTypeCode: 'UPDATE',
            oldData: $oldData,
            newData: $model->getAttributes(),
            notes: $notes
        );
    }

    public function logDeleted(Model $model, ?string $notes = null): void
    {
        $this->log(
            tableName: $model->getTable(),
            recordPk: (string) $model->getKey(),
            actionTypeCode: 'DELETE',
            oldData: $model->getAttributes(),
            newData: null,
            notes: $notes
        );
    }

    public function log(
        string $tableName,
        string $recordPk,
        string $actionTypeCode,
        ?array $oldData = null,
        ?array $newData = null,
        ?string $notes = null
    ): void {
        SystemChangeLog::query()->create([
            'table_name' => $tableName,
            'record_pk' => $recordPk,
            'action_type_code' => $actionTypeCode,
            'changed_by' => auth()->user()?->employee_id,
            'changed_at' => now(),
            'old_data' => $oldData,
            'new_data' => $newData,
            'notes' => $notes,
        ]);
    }
}