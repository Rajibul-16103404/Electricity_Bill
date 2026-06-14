<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'consumer_id_id',
    'order_no',
    'token',
    'seq',
    'rent',
    'demand_charge',
    'pfc',
    'tax',
    'subsidy_amount',
    'purchase_amount',
    'total_amount',
    'purchase_energy',
    'sale_name',
    'purchase_date',
    'debt_amount',
    'paid_amount',
])]
class Recharge extends Model
{
    /** @use HasFactory<\Database\Factories\RechargeFactory> */
    use HasFactory;

    /**
     * Get the consumer that owns the recharge.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<ConsumerId, $this>
     */
    public function consumerId(): BelongsTo
    {
        return $this->belongsTo(ConsumerId::class, 'consumer_id_id');
    }
}
