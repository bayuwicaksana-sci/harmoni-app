<?php

namespace Database\Factories;

use App\Enums\SettlementStatus;
use App\Models\Employee;
use App\Models\Settlement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settlement>
 */
class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'settlement_number' => 'SET-'.$this->faker->unique()->numerify('######'),
            'submitter_id' => Employee::factory(),
            'refund_amount' => $this->faker->randomFloat(2, 0, 1000000),
            'submit_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status' => $this->faker->randomElement(SettlementStatus::cases()),
        ];
    }
}
