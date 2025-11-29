<?php

namespace Database\Factories;

use App\Models\JobGrade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobGrade>
 */
class JobGradeFactory extends Factory
{
    protected $model = JobGrade::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grade' => $this->faker->unique()->randomElement(['A', 'B', 'C', 'D', 'E']).$this->faker->numberBetween(1, 5),
            'numeric_value' => $this->faker->numberBetween(1, 15),
        ];
    }
}
