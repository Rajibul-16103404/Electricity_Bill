<?php

namespace Database\Seeders;

use App\Models\ConsumerId;
use Illuminate\Database\Seeder;

class ConsumerIdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConsumerId::factory()->count(10)->create();
    }
}
