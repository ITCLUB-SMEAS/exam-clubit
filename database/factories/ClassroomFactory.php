<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['X', 'XI', 'XII']) . ' ' . fake()->randomElement(['IPA', 'IPS']) . ' ' . fake()->numberBetween(1, 5),
        ];
    }
}
