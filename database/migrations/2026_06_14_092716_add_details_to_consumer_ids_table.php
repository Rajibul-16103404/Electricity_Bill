<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consumer_ids', function (Blueprint $table) {
            $table->string('customer_name')->nullable();
            $table->string('address')->nullable();
            $table->string('mobile')->nullable();
            $table->string('billing_office')->nullable();
            $table->string('feeder_name')->nullable();
            $table->string('meter_no')->nullable();
            $table->string('sanction_load')->nullable();
            $table->string('tariff')->nullable();
            $table->string('meter_type')->nullable();
            $table->string('meter_status')->nullable();
            $table->string('installation_date')->nullable();
            $table->string('min_recharge')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumer_ids', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name', 'address', 'mobile', 'billing_office', 'feeder_name',
                'meter_no', 'sanction_load', 'tariff', 'meter_type', 'meter_status',
                'installation_date', 'min_recharge'
            ]);
        });
    }
};
