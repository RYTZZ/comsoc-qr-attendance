<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RowLock
{
    public static function acquire(string $modelClass, int|string $id): ?Model
    {
        $model = new $modelClass();
        $connection = $model->getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            $table = $model->getTable();
            $key = $model->getKeyName();
            $connection->update("update \"{$table}\" set \"{$key}\" = \"{$key}\" where \"{$key}\" = ?", [$id]);
        }

        return $modelClass::query()->whereKey($id)->lockForUpdate()->first();
    }

    public static function acquireTable(string $table): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() === 'sqlite') {
            $connection->update("update \"{$table}\" set \"id\" = \"id\" where 0 = 1");
            return;
        }

        $connection->table($table)->lockForUpdate()->get(['id']);
    }
}
