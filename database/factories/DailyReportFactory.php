<?php

namespace Database\Factories;

use App\Models\ConsumerId;
use App\Models\DailyReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyReport>
 */
class DailyReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'consumer_id_id' => ConsumerId::factory(),
            'date' => $this->faker->unique()->date(),
            'remaining_balance' => $this->faker->randomFloat(2, 10, 1000),
            'recharge_amount' => $this->faker->randomFloat(2, 0, 500),
            'usage_taka' => $this->faker->randomFloat(2, 0, 200),
            'usage_kwh' => $this->faker->randomFloat(2, 0, 50),
        ];
    }
}
