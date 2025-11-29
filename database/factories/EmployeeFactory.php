<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\JobGrade;
use App\Models\JobTitle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'internal_id' => $this->faker->unique()->numerify('EMP######'),
            'supervisor_id' => null, // Can be set separately if needed
            'job_title_id' => JobTitle::factory(),
            'job_grade_id' => JobGrade::factory(),
            'bank_name' => $this->faker->company(),
            'bank_account_number' => $this->faker->unique()->bankAccountNumber(),
            'bank_cust_name' => $this->faker->name(),
        ];
    }
}
