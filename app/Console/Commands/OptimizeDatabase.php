<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeDatabase extends Command
{
    protected $signature = 'db:optimize';
    protected $description = 'Optimize database tables';

    public function handle(): int
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'mysql') {
            $this->error('This command only supports MySQL/MariaDB');
            return 1;
        }

        $this->info('Optimizing database tables...');

        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $key = "Tables_in_{$dbName}";

        foreach ($tables as $table) {
            $tableName = $table->$key;
            $this->info("Optimizing: {$tableName}");
            DB::statement("OPTIMIZE TABLE {$tableName}");
        }

        $this->info('Database optimization completed!');
        return 0;
    }
}
