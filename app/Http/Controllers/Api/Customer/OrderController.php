<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * A signed-in shopper's own orders.
 *
 * Scoped through the relation rather than a where clause on a request value,
 * so there is no route by which one customer can read another's order.
 */
class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->paginate(10);

        return OrderResource::collection($orders);
    }
}
