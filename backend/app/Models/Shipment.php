<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id', 'customer', 'origin', 'carrier', 'eta', 'bay', 'clearance', 'exceptions', 'events'])]
class Shipment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['exceptions' => 'array', 'events' => 'array'];
    }

    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class);
    }

    public function toWorkspaceArray(): array
    {
        return [
            'id' => $this->id, 'customer' => $this->customer, 'origin' => $this->origin,
            'carrier' => $this->carrier, 'eta' => $this->eta, 'bay' => $this->bay,
            'clearance' => $this->clearance,
            'parcels' => $this->parcels->map(fn (Parcel $parcel): array => [
                'id' => $parcel->id, 'description' => $parcel->description,
                'received' => $parcel->received, 'damaged' => $parcel->damaged,
            ])->all(),
            'exceptions' => $this->exceptions ?? [], 'events' => $this->events ?? [],
        ];
    }
}
