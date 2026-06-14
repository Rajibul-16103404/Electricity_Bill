<?php

namespace Database\Factories;

use App\Models\Recharge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recharge>
 */
class RechargeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consumer_id_id' => \App\Models\ConsumerId::factory(),
            'order_no' => $this->faker->uuid(),
            'token' => $this->faker->numerify('#### #### #### #### ####'),
            'seq' => $this->faker->numerify('#'),
            'rent' => 250.00,
            'demand_charge' => 1050.00,
            'pfc' => 0,
            'tax' => 71.43,
            'subsidy_amount' => -5.86,
            'purchase_amount' => 134.43,
            'total_amount' => 1500.00,
            'purchase_energy' => 29.03,
            'sale_name' => 'UVS_BoguraSD1',
            'purchase_date' => $this->faker->dateTime()->format('d-M-Y g:i A'),
            'debt_amount' => 0,
            'paid_amount' => 1500.00,
        ];
    }
}
