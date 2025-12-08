<?php

namespace App\Console\Commands;

use App\Models\{Classroom, Lesson, Room};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheWarmup extends Command
{
    protected $signature = 'cache:warmup';
    protected $description = 'Warm up application cache';

    public function handle(): int
    {
        $this->info('Warming up cache...');

        Cache::remember('classrooms_all', 3600, fn() => Classroom::all());
        Cache::remember('lessons_all', 3600, fn() => Lesson::all());
        Cache::remember('rooms_with_count', 300, fn() => Room::withCount('students')->get());

        $this->info('Cache warmed up successfully!');
        return 0;
    }
}
