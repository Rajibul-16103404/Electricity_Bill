<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsumerIdResource extends JsonResource
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
            'consumer_id' => $this->consumer_id,
            'customer_name' => $this->customer_name,
            'father_husband_name' => $this->father_husband_name,
            'address' => $this->address,
            'mobile' => $this->mobile,
            'billing_office' => $this->billing_office,
            'feeder_name' => $this->feeder_name,
            'meter_no' => $this->meter_no,
            'sanction_load' => $this->sanction_load,
            'tariff' => $this->tariff,
            'meter_type' => $this->meter_type,
            'meter_status' => $this->meter_status,
            'installation_date' => $this->installation_date,
            'min_recharge' => $this->min_recharge,
            'remaining_balance' => $this->remaining_balance !== null ? (float) $this->remaining_balance : null,
            'balance_updated_at' => $this->balance_updated_at,
            'recharges' => RechargeResource::collection($this->whenLoaded('recharges')),
            'monthly_usages' => MonthlyUsageResource::collection($this->whenLoaded('monthlyUsages')),
            'daily_reports' => DailyReportResource::collection($this->whenLoaded('dailyReports')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
