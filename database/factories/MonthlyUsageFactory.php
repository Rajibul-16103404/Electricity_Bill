<?php

namespace Database\Factories;

use App\Models\ConsumerId;
use App\Models\MonthlyUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyUsage>
 */
class MonthlyUsageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consumer_id_id' => ConsumerId::factory(),
            'year' => (int) $this->faker->year(),
            'month' => $this->faker->monthName(),
            'total_recharge' => 699.00,
            'rebate' => -2.52,
            'used_electricity_taka' => 212.06,
            'meter_rent' => 160.00,
            'demand_charge' => 336.00,
            'pfc_charge' => 0,
            'paid_arrear_penalty' => 0,
            'vat' => 33.29,
            'total_usage_deduction' => 741.35,
            'meter_balance' => 256.20,
            'used_electricity_kwh' => 45.80,
        ];
    }
}
