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
        Schema::table('consumer_ids', function (Blueprint $table) {
            $table->string('father_husband_name')->nullable()->after('customer_name');
            $table->decimal('remaining_balance', 10, 2)->nullable()->after('min_recharge');
            $table->string('balance_updated_at')->nullable()->after('remaining_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumer_ids', function (Blueprint $table) {
            $table->dropColumn(['father_husband_name', 'remaining_balance', 'balance_updated_at']);
        });
    }
};
