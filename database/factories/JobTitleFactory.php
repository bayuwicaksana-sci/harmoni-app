<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\JobLevel;
use App\Models\JobTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobTitle>
 */
class JobTitleFactory extends Factory
{
    protected $model = JobTitle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->jobTitle(),
            'code' => $this->faker->unique()->lexify('JT-????'),
            'department_id' => Department::factory(),
            'job_level_id' => JobLevel::factory(),
            'is_active' => true,
        ];
    }
}
