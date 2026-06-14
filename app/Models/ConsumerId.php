<?php

namespace App\Models;

use Database\Factories\ConsumerIdFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'consumer_id',
    'customer_name',
    'father_husband_name',
    'address',
    'mobile',
    'billing_office',
    'feeder_name',
    'meter_no',
    'sanction_load',
    'tariff',
    'meter_type',
    'meter_status',
    'installation_date',
    'min_recharge',
    'remaining_balance',
    'balance_updated_at',
])]
class ConsumerId extends Authenticatable
{
    /** @use HasFactory<ConsumerIdFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the recharges for the consumer.
     *
     * @return HasMany<Recharge>
     */
    public function recharges(): HasMany
    {
        return $this->hasMany(Recharge::class, 'consumer_id_id')->orderBy('id', 'asc');
    }

    /**
     * Get the monthly usage history for the consumer.
     *
     * @return HasMany<MonthlyUsage>
     */
    public function monthlyUsages(): HasMany
    {
        return $this->hasMany(MonthlyUsage::class, 'consumer_id_id')->orderBy('id', 'asc');
    }

    /**
     * Get the daily reports for the consumer.
     *
     * @return HasMany<DailyReport>
     */
    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class, 'consumer_id_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'consumer_id';
    }
}
