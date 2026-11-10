<?php

namespace Database\Seeders;

use App\Models\Shipment;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['MW-1048', 'Nordic Living', 'Copenhagen, DK', 'Northline Logistics', '09:30', 'Bay 03', 'Pending', 12, 8],
            ['MW-1049', 'Atelier Home', 'Lyon, FR', 'Eurofreight', '10:15', 'Bay 01', 'Held', 8, 8],
            ['MW-1050', 'Studio Supply', 'Rotterdam, NL', 'Northline Logistics', '11:00', 'Bay 04', 'Cleared', 16, 0],
            ['MW-1051', 'Field & Form', 'Dublin, IE', 'Greenway Transport', '11:45', 'Bay 02', 'Pending', 10, 0],
            ['MW-1046', 'Everyday Objects', 'Hamburg, DE', 'Eurofreight', '08:00', 'Bay 01', 'Cleared', 6, 6],
        ];
        foreach ($rows as [$id, $customer, $origin, $carrier, $eta, $bay, $clearance, $count, $received]) {
            if (Shipment::whereKey($id)->exists()) {
                continue;
            }
            $shipment = Shipment::create(compact('id', 'customer', 'origin', 'carrier', 'eta', 'bay', 'clearance') + [
                'exceptions' => $id === 'MW-1049' ? [['id' => 'EX-001', 'parcelId' => 'MW-1049-P003', 'type' => 'Damage', 'note' => 'Outer carton crushed on arrival. Contents require inspection.', 'resolved' => false, 'createdAt' => '2026-11-10T09:10:00Z']] : [],
                'events' => [['id' => 'EV-'.$id, 'at' => '2026-11-10T08:00:00Z', 'action' => 'Manifest imported', 'detail' => $count.' parcels expected from '.$origin, 'actor' => 'PoC coordinator']],
            ]);
            for ($index = 1; $index <= $count; $index++) {
                $shipment->parcels()->create(['id' => $id.'-P'.str_pad((string) $index, 3, '0', STR_PAD_LEFT), 'description' => ['Homewares', 'Textiles', 'Accessories'][($index - 1) % 3], 'received' => $index <= $received, 'damaged' => $id === 'MW-1049' && $index === 3]);
            }
        }
    }
}
