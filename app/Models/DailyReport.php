<?php

namespace App\Models;

use Database\Factories\DailyReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'consumer_id_id',
    'date',
    'remaining_balance',
    'recharge_amount',
    'usage_taka',
    'usage_kwh',
])]
class DailyReport extends Model
{
    /** @use HasFactory<DailyReportFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'remaining_balance' => 'float',
            'recharge_amount' => 'float',
            'usage_taka' => 'float',
            'usage_kwh' => 'float',
        ];
    }

    /**
     * Get the consumer ID associated with this daily report.
     *
     * @return BelongsTo<ConsumerId, $this>
     */
    public function consumerId(): BelongsTo
    {
        return $this->belongsTo(ConsumerId::class, 'consumer_id_id');
    }
}
