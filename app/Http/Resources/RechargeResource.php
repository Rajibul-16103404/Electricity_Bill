<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RechargeResource extends JsonResource
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
            'order_no' => $this->order_no,
            'token' => $this->token,
            'seq' => $this->seq,
            'rent' => (float) $this->rent,
            'demand_charge' => (float) $this->demand_charge,
            'pfc' => (float) $this->pfc,
            'tax' => (float) $this->tax,
            'subsidy_amount' => (float) $this->subsidy_amount,
            'purchase_amount' => (float) $this->purchase_amount,
            'total_amount' => (float) $this->total_amount,
            'purchase_energy' => (float) $this->purchase_energy,
            'sale_name' => $this->sale_name,
            'purchase_date' => $this->purchase_date,
            'debt_amount' => (float) $this->debt_amount,
            'paid_amount' => (float) $this->paid_amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
