<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['name', 'capacity'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function isFull(): bool
    {
        return $this->students()->count() >= $this->capacity;
    }

    public function availableSlots(): int
    {
        return max(0, $this->capacity - $this->students()->count());
    }

    public static function getRandomAvailable(): ?self
    {
        return static::withCount('students')
            ->havingRaw('students_count < capacity')
            ->inRandomOrder()
            ->first();
    }
}
