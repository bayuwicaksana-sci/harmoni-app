<?php

namespace Database\Factories;

use App\Models\JobLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobLevel>
 */
class JobLevelFactory extends Factory
{
    protected $model = JobLevel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level' => $this->faker->unique()->numberBetween(1, 10),
            'name' => $this->faker->randomElement(['Staff', 'Senior', 'Manager', 'Director', 'Executive']),
        ];
    }
}
