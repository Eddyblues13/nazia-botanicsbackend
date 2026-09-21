<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The states the shop currently delivers to, for the checkout dropdown.
 *
 * Only active zones: an inactive one has no agreed price yet, so offering it
 * would promise a delivery nobody has costed.
 */
class DeliveryZoneController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $zones = DeliveryZone::query()->active()->ordered()->get();

        return JsonResource::collection($zones->map(fn (DeliveryZone $zone) => [
            'state' => $zone->state,
            'fee' => $zone->fee,
            'delivery_period' => $zone->delivery_period,
        ]));
    }
}
