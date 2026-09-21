<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

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

    /**
     * Adds an order placed as a guest to the account.
     *
     * Checkout does not require an account, so most orders arrive with no owner
     * and the customer may only sign up afterwards. This is how they connect
     * the two.
     *
     * Two things are required, not one: the reference from the confirmation,
     * and an account whose email matches the one on the order. A reference
     * alone is four random characters — around 1.7 million per day, which is
     * guessable given enough attempts — so on its own it is not proof that the
     * order belongs to whoever is asking. Requiring the email as well means a
     * guessed reference is useless unless it happens to belong to the address
     * already on the account. The route is rate limited on top of that.
     */
    public function claim(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:40'],
        ]);

        $user = $request->user();

        $order = Order::query()
            ->where('reference', strtoupper(trim($data['reference'])))
            ->first();

        $isTheirs = $order
            && $order->customer_email !== null
            && strcasecmp($order->customer_email, $user->email) === 0;

        // One message for every failure — wrong reference, someone else's
        // order, or one already claimed. Distinguishing them would turn this
        // into a way of testing which references exist.
        if (! $order || ! $isTheirs || ($order->user_id !== null && $order->user_id !== $user->id)) {
            throw ValidationException::withMessages([
                'reference' => 'We could not find an order with that reference on your email address.',
            ]);
        }

        if ($order->user_id === null) {
            $order->forceFill(['user_id' => $user->id])->save();
        }

        return response()->json([
            'message' => 'Order added to your account.',
            'data' => new OrderResource($order->load('items')),
        ]);
    }
}
