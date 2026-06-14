<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consumer_id_id' => $this->consumer_id_id,
            'date' => $this->date instanceof Carbon ? $this->date->toDateString() : $this->date,
            'remaining_balance' => (float) $this->remaining_balance,
            'recharge_amount' => (float) $this->recharge_amount,
            'usage_taka' => (float) $this->usage_taka,
            'usage_kwh' => (float) $this->usage_kwh,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
