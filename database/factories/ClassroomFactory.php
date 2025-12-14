<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->regexify('[A-Z]{2}[0-9]{3}'),
        ];
    }
}
