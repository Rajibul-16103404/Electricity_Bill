<?php

namespace Database\Factories;

use App\Models\ConsumerId;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumerId>
 */
class ConsumerIdFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consumer_id' => $this->faker->unique()->numerify('########'),
        ];
    }
}
