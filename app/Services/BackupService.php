<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class BackupService
{
    protected string $backupPath = 'backups';

    public function createDatabaseBackup(): ?string
    {
        $filename = 'db_backup_' . Carbon::now()->format('Y-m-d_His') . '.sql.enc';
        $path = $this->backupPath . '/' . $filename;

        try {
            $pdo = DB::connection()->getPdo();
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
                        $values = $chunk->map(function ($row) use ($pdo) {
                            return '(' . collect((array) $row)->map(function ($value) use ($pdo) {
                                if (is_null($value)) return 'NULL';
                                return $pdo->quote($value);
                            })->implode(', ') . ')';
                        })->implode(",\n");

                        $sql .= "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n{$values};\n\n";
                    }
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Encrypt backup content
            $encrypted = Crypt::encryptString($sql);
            Storage::disk('local')->put($path, $encrypted);

            Log::info('Database backup created (encrypted)', ['file' => $filename]);

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
        
        return collect($files)
            ->filter(fn($file) => str_ends_with($file, '.sql.enc') || str_ends_with($file, '.sql'))
            ->map(function ($file) {
                $name = basename($file);
                return [
                    'name' => $name,
                    'path' => $file,
                    'size' => Storage::disk('local')->size($file),
                    'encrypted' => str_ends_with($name, '.enc'),
                    'created_at' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file)),
                ];
            })->sortByDesc('created_at')->values()->all();
    }

    public function downloadBackup(string $filename): ?string
    {
        // Validate filename to prevent path traversal (support both encrypted and legacy)
        if (!preg_match('/^db_backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql(\.enc)?$/', $filename)) {
            return null;
        }

        $path = $this->backupPath . '/' . $filename;
        
        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        // If encrypted, decrypt to temp file for download
        if (str_ends_with($filename, '.enc')) {
            $encrypted = Storage::disk('local')->get($path);
            $decrypted = Crypt::decryptString($encrypted);
            
            $tempPath = $this->backupPath . '/temp_' . str_replace('.enc', '', $filename);
            Storage::disk('local')->put($tempPath, $decrypted);
            
            return Storage::disk('local')->path($tempPath);
        }

        return Storage::disk('local')->path($path);
    }

    public function deleteBackup(string $filename): bool
    {
        // Validate filename to prevent path traversal
        if (!preg_match('/^db_backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql(\.enc)?$/', $filename)) {
            return false;
        }

        $path = $this->backupPath . '/' . $filename;
        return Storage::disk('local')->delete($path);
    }

    /**
     * Clean up temporary decrypted files
     */
    public function cleanupTempFiles(): int
    {
        $deleted = 0;
        $files = Storage::disk('local')->files($this->backupPath);

        foreach ($files as $file) {
            if (str_starts_with(basename($file), 'temp_')) {
                Storage::disk('local')->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
