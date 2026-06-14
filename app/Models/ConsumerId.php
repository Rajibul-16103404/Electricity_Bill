<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['consumer_id'])]
class ConsumerId extends Model
{
    /** @use HasFactory<\Database\Factories\ConsumerIdFactory> */
    use HasFactory;
}
