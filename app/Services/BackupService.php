<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupService
{
    protected string $backupPath = 'backups';

    public function createDatabaseBackup(): ?string
    {
        $filename = 'db_backup_' . Carbon::now()->format('Y-m-d_His') . '.sql';
        $path = $this->backupPath . '/' . $filename;

        try {
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . config('database.connections.mysql.database');
            
            $sql = "-- Database Backup\n-- Generated: " . now()->toDateTimeString() . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                
                // Get create table statement
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

                // Get data
                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $columns = array_keys((array) $rows->first());
                    $columnList = '`' . implode('`, `', $columns) . '`';

                    foreach ($rows->chunk(100) as $chunk) {
                        $values = $chunk->map(function ($row) {
                            return '(' . collect((array) $row)->map(function ($value) {
                                if (is_null($value)) return 'NULL';
                                return "'" . addslashes($value) . "'";
                            })->implode(', ') . ')';
                        })->implode(",\n");

                        $sql .= "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n{$values};\n\n";
                    }
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            Storage::disk('local')->put($path, $sql);

            Log::info('Database backup created', ['file' => $filename]);

            return $path;
        } catch (\Exception $e) {
            Log::error('Database backup failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function cleanOldBackups(int $keepDays = 7): int
    {
        $deleted = 0;
        $files = Storage::disk('local')->files($this->backupPath);
        $cutoff = Carbon::now()->subDays($keepDays);

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file));
            if ($lastModified->lt($cutoff)) {
                Storage::disk('local')->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    public function listBackups(): array
    {
        $files = Storage::disk('local')->files($this->backupPath);
        
        return collect($files)->map(function ($file) {
            return [
                'name' => basename($file),
                'path' => $file,
                'size' => Storage::disk('local')->size($file),
                'created_at' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file)),
            ];
        })->sortByDesc('created_at')->values()->all();
    }

    public function downloadBackup(string $filename): ?string
    {
        $path = $this->backupPath . '/' . $filename;
        
        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        return Storage::disk('local')->path($path);
    }

    public function deleteBackup(string $filename): bool
    {
        $path = $this->backupPath . '/' . $filename;
        return Storage::disk('local')->delete($path);
    }
}
