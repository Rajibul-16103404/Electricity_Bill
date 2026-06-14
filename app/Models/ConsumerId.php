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
])]
class ConsumerId extends Authenticatable
{
    /** @use HasFactory<ConsumerIdFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the recharges for the consumer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Recharge>
     */
    public function recharges(): HasMany
    {
        return $this->hasMany(Recharge::class, 'consumer_id_id');
    }
}
