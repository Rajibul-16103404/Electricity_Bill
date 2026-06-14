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
        Schema::create('recharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumer_id_id')->constrained('consumer_ids')->cascadeOnDelete();
            $table->string('order_no')->nullable();
            $table->text('token')->nullable();
            $table->string('seq')->nullable();
            $table->decimal('rent', 10, 2)->default(0);
            $table->decimal('demand_charge', 10, 2)->default(0);
            $table->decimal('pfc', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('subsidy_amount', 10, 2)->default(0);
            $table->decimal('purchase_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('purchase_energy', 10, 2)->default(0);
            $table->string('sale_name')->nullable();
            $table->string('purchase_date')->nullable();
            $table->decimal('debt_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recharges');
    }
};
