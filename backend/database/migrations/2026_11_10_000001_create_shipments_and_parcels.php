<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table): void {
            $table->string('id', 40)->primary();
            $table->string('customer');
            $table->string('origin');
            $table->string('carrier');
            $table->string('eta', 5);
            $table->string('bay', 40);
            $table->string('clearance', 16)->default('Pending');
            $table->json('exceptions');
            $table->json('events');
            $table->timestamps();
        });
        Schema::create('parcels', function (Blueprint $table): void {
            $table->string('id', 100)->primary();
            $table->string('shipment_id', 40);
            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            $table->string('description');
            $table->boolean('received')->default(false);
            $table->boolean('damaged')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcels');
        Schema::dropIfExists('shipments');
    }
};
