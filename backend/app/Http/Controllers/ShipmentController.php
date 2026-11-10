<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Shipment::with('parcels')->orderByDesc('id')->get()
            ->map(fn (Shipment $shipment): array => $shipment->toWorkspaceArray())->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shipment' => ['required', 'array'],
            'shipment.id' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9-]+$/', 'unique:shipments,id'],
            'shipment.customer' => ['required', 'string', 'max:255'],
            'shipment.origin' => ['required', 'string', 'max:255'],
            'shipment.carrier' => ['required', 'string', 'max:255'],
            'shipment.eta' => ['required', 'date_format:H:i'],
            'shipment.bay' => ['required', 'string', 'max:40'],
            'shipment.parcels' => ['required', 'array', 'min:1', 'max:5000'],
            'shipment.parcels.*.id' => ['required', 'string', 'max:100', 'distinct', 'unique:parcels,id'],
            'shipment.parcels.*.description' => ['required', 'string', 'max:255'],
        ])['shipment'];

        DB::transaction(function () use ($data): void {
            $shipment = Shipment::create([
                ...collect($data)->except('parcels')->all(), 'clearance' => 'Pending', 'exceptions' => [],
                'events' => [$this->event('Manifest imported', count($data['parcels']).' parcels expected')],
            ]);
            foreach ($data['parcels'] as $parcel) {
                $shipment->parcels()->create(['id' => $parcel['id'], 'description' => $parcel['description'], 'received' => false, 'damaged' => false]);
            }
        });

        return $this->index()->setStatusCode(201);
    }

    public function receive(Request $request, string $reference): JsonResponse
    {
        $data = $request->validate(['parcelId' => ['required', 'string', 'max:100']]);
        DB::transaction(function () use ($reference, $data): void {
            $shipment = Shipment::whereKey($reference)->lockForUpdate()->firstOrFail();
            $parcel = $shipment->parcels()->whereKey($data['parcelId'])->first();
            if ($parcel === null) {
                throw ValidationException::withMessages(['parcelId' => 'This parcel is not on the manifest.']);
            }
            if ($parcel->received) {
                throw ValidationException::withMessages(['parcelId' => 'This parcel has already been checked in.']);
            }
            $parcel->update(['received' => true]);
            $shipment->update(['events' => [$this->event('Parcel received', $parcel->id), ...$shipment->events]]);
        });

        return $this->index();
    }

    public function report(Request $request, string $reference): JsonResponse
    {
        $data = $request->validate([
            'parcelId' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['Damage', 'Missing', 'Unexpected'])],
            'note' => ['required', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($reference, $data): void {
            $shipment = Shipment::whereKey($reference)->lockForUpdate()->firstOrFail();
            $parcel = $shipment->parcels()->whereKey($data['parcelId'])->first();
            if ($data['type'] !== 'Unexpected' && $parcel === null) {
                throw ValidationException::withMessages(['parcelId' => 'Choose a parcel on this manifest.']);
            }
            foreach ($shipment->exceptions as $exception) {
                if ($exception['parcelId'] === $data['parcelId'] && $exception['type'] === $data['type'] && ! $exception['resolved']) {
                    throw ValidationException::withMessages(['parcelId' => 'An open discrepancy of this type already exists.']);
                }
            }
            if ($data['type'] === 'Damage') {
                $parcel->update(['damaged' => true]);
            }
            $shipment->update([
                'exceptions' => [['id' => (string) Str::uuid(), ...$data, 'resolved' => false, 'createdAt' => now()->toIso8601String()], ...$shipment->exceptions],
                'events' => [$this->event($data['type'].' reported', $data['parcelId'].': '.$data['note']), ...$shipment->events],
            ]);
        });

        return $this->index();
    }

    public function resolve(string $reference, string $exceptionId): JsonResponse
    {
        DB::transaction(function () use ($reference, $exceptionId): void {
            $shipment = Shipment::whereKey($reference)->lockForUpdate()->firstOrFail();
            $exceptions = collect($shipment->exceptions);
            $exception = $exceptions->firstWhere('id', $exceptionId);
            abort_if($exception === null, 404);
            if ($exception['resolved']) {
                throw ValidationException::withMessages(['exceptionId' => 'This discrepancy is already resolved.']);
            }
            $shipment->update([
                'exceptions' => $exceptions->map(fn (array $item): array => $item['id'] === $exceptionId ? [...$item, 'resolved' => true] : $item)->all(),
                'events' => [$this->event('Discrepancy resolved', $exception['parcelId'].': '.$exception['type']), ...$shipment->events],
            ]);
        });

        return $this->index();
    }

    private function event(string $action, string $detail): array
    {
        return ['id' => (string) Str::uuid(), 'at' => now()->toIso8601String(), 'action' => $action, 'detail' => $detail, 'actor' => 'PoC operator'];
    }
}
