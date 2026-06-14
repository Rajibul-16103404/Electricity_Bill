<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthlyUsageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consumer_id_id' => $this->consumer_id_id,
            'year' => $this->year,
            'month' => $this->month,
            'total_recharge' => (float) $this->total_recharge,
            'rebate' => (float) $this->rebate,
            'used_electricity_taka' => (float) $this->used_electricity_taka,
            'meter_rent' => (float) $this->meter_rent,
            'demand_charge' => (float) $this->demand_charge,
            'pfc_charge' => (float) $this->pfc_charge,
            'paid_arrear_penalty' => (float) $this->paid_arrear_penalty,
            'vat' => (float) $this->vat,
            'total_usage_deduction' => (float) $this->total_usage_deduction,
            'meter_balance' => (float) $this->meter_balance,
            'used_electricity_kwh' => (float) $this->used_electricity_kwh,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
