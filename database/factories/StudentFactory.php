<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nisn' => fake()->unique()->numerify('##########'),
            'name' => fake()->name(),
            'password' => Hash::make('password'),
            'gender' => fake()->randomElement(['L', 'P']),
            'classroom_id' => Classroom::factory(),
            'locale' => 'id',
        ];
    }
}
