<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

/**
 * Delivery zones, as the shop manages them.
 *
 * Unlike the storefront list this includes inactive states, which is where a
 * state sits until someone has decided what delivery there costs.
 */
class DeliveryZoneController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $zones = DeliveryZone::query()->ordered()->get();

        return JsonResource::collection($zones->map(fn (DeliveryZone $zone) => $this->present($zone)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $zone = DeliveryZone::create($data);

        return response()->json(['data' => $this->present($zone)], 201);
    }

    public function update(Request $request, DeliveryZone $deliveryZone): JsonResponse
    {
        $data = $request->validate($this->rules($deliveryZone));

        $deliveryZone->update($data);

        return response()->json(['data' => $this->present($deliveryZone->fresh())]);
    }

    public function destroy(DeliveryZone $deliveryZone): JsonResponse
    {
        $deliveryZone->delete();

        return response()->json(['message' => 'Zone removed.']);
    }

    private function rules(?DeliveryZone $zone = null): array
    {
        return [
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('delivery_zones', 'name')->ignore($zone?->getKey()),
            ],
            // The areas this zone covers. Sent as a list; the dashboard lets
            // the shop type them comma separated.
            'areas' => ['nullable', 'array', 'max:80'],
            'areas.*' => ['string', 'max:60'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            // Whole naira, like every other price here. Free delivery is a
            // legitimate choice, so zero is allowed.
            'fee' => ['required', 'integer', 'min:0', 'max:10000000'],
            'delivery_period' => ['required', 'string', 'max:80'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    private function present(DeliveryZone $zone): array
    {
        return [
            'id' => $zone->id,
            'name' => $zone->name,
            'areas' => $zone->areas ?? [],
            'fee' => $zone->fee,
            'delivery_period' => $zone->delivery_period,
            'is_active' => $zone->is_active,
            'sort_order' => $zone->sort_order,
        ];
    }
}
