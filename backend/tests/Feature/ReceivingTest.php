<?php

namespace Tests\Feature;

use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivingTest extends TestCase
{
    use RefreshDatabase;

    public function test_receiving_is_persistent_and_does_not_clear_customs(): void
    {
        $this->seed(WarehouseSeeder::class);
        $this->postJson('/api/shipments/MW-1048/receive', ['parcelId' => 'MW-1048-P009'])->assertOk();
        $this->assertDatabaseHas('parcels', ['id' => 'MW-1048-P009', 'received' => true]);
        $this->assertDatabaseHas('shipments', ['id' => 'MW-1048', 'clearance' => 'Pending']);
        $this->getJson('/api/shipments')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_duplicate_and_unknown_labels_are_rejected(): void
    {
        $this->seed(WarehouseSeeder::class);
        $this->postJson('/api/shipments/MW-1048/receive', ['parcelId' => 'MW-1048-P001'])->assertUnprocessable();
        $this->postJson('/api/shipments/MW-1048/receive', ['parcelId' => 'MW-1050-P001'])->assertUnprocessable();
        $this->assertDatabaseHas('parcels', ['id' => 'MW-1050-P001', 'received' => false]);
    }

    public function test_damage_reporting_and_resolution_preserve_history(): void
    {
        $this->seed(WarehouseSeeder::class);
        $response = $this->postJson('/api/shipments/MW-1048/exceptions', ['parcelId' => 'MW-1048-P009', 'type' => 'Damage', 'note' => 'Crushed carton'])->assertOk();
        $shipment = collect($response->json('data'))->firstWhere('id', 'MW-1048');
        $exceptionId = $shipment['exceptions'][0]['id'];
        $this->assertDatabaseHas('parcels', ['id' => 'MW-1048-P009', 'received' => false, 'damaged' => true]);
        $resolved = $this->postJson('/api/shipments/MW-1048/exceptions/'.$exceptionId.'/resolve')->assertOk();
        $updated = collect($resolved->json('data'))->firstWhere('id', 'MW-1048');
        $this->assertTrue($updated['exceptions'][0]['resolved']);
        $this->assertCount(3, $updated['events']);
        $this->postJson('/api/shipments/MW-1048/exceptions/'.$exceptionId.'/resolve')->assertUnprocessable();
    }

    public function test_api_is_disabled_in_production_until_authentication_exists(): void
    {
        $this->app->instance('env', 'production');
        $this->getJson('/api/shipments')->assertStatus(503);
    }
}
