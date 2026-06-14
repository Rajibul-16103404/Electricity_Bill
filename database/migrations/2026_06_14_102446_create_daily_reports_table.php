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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumer_id_id')->constrained('consumer_ids')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('remaining_balance', 10, 2);
            $table->decimal('recharge_amount', 10, 2)->default(0);
            $table->decimal('usage_taka', 10, 2)->default(0);
            $table->decimal('usage_kwh', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['consumer_id_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
