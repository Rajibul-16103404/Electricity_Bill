<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumer_id_id')->constrained('consumer_ids')->cascadeOnDelete();
            $table->integer('year');
            $table->string('month');
            $table->decimal('total_recharge', 10, 2)->default(0);
            $table->decimal('rebate', 10, 2)->default(0);
            $table->decimal('used_electricity_taka', 10, 2)->default(0);
            $table->decimal('meter_rent', 10, 2)->default(0);
            $table->decimal('demand_charge', 10, 2)->default(0);
            $table->decimal('pfc_charge', 10, 2)->default(0);
            $table->decimal('paid_arrear_penalty', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->decimal('total_usage_deduction', 10, 2)->default(0);
            $table->decimal('meter_balance', 10, 2)->default(0);
            $table->decimal('used_electricity_kwh', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_usages');
    }
};
