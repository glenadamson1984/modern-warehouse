<?php

use App\Http\Controllers\ShipmentController;
use App\Http\Middleware\LocalPoc;
use Illuminate\Support\Facades\Route;

Route::middleware(LocalPoc::class)->group(function (): void {
    Route::get('/shipments', [ShipmentController::class, 'index']);
    Route::post('/shipments', [ShipmentController::class, 'store']);
    Route::post('/shipments/{reference}/receive', [ShipmentController::class, 'receive']);
    Route::post('/shipments/{reference}/exceptions', [ShipmentController::class, 'report']);
    Route::post('/shipments/{reference}/exceptions/{exceptionId}/resolve', [ShipmentController::class, 'resolve']);
});
