<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'shipment_id', 'description', 'received', 'damaged'])]
class Parcel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['received' => 'boolean', 'damaged' => 'boolean'];
    }
}
