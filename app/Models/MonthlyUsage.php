<?php

namespace App\Models;

use Database\Factories\MonthlyUsageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'consumer_id_id',
    'year',
    'month',
    'total_recharge',
    'rebate',
    'used_electricity_taka',
    'meter_rent',
    'demand_charge',
    'pfc_charge',
    'paid_arrear_penalty',
    'vat',
    'total_usage_deduction',
    'meter_balance',
    'used_electricity_kwh',
])]
class MonthlyUsage extends Model
{
    /** @use HasFactory<MonthlyUsageFactory> */
    use HasFactory;

    /**
     * Get the consumer that owns this monthly usage record.
     *
     * @return BelongsTo<ConsumerId, $this>
     */
    public function consumerId(): BelongsTo
    {
        return $this->belongsTo(ConsumerId::class, 'consumer_id_id');
    }
}
