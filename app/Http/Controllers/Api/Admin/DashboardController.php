<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminOrderResource;
use App\Http\Resources\ContactMessageResource;
use App\Http\Resources\WaitlistSignupResource;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\WaitlistSignup;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();

        $revenueThisMonth = $this->revenueSince($thisMonth);
        $revenueLastMonth = $this->revenueBetween($lastMonth, $thisMonth);

        return response()->json([
            'data' => [
                'revenue_total' => (int) Order::whereIn('status', Order::REVENUE_STATUSES)->sum('subtotal'),
                'revenue_this_month' => $revenueThisMonth,
                'revenue_last_month' => $revenueLastMonth,
                'revenue_change' => $this->percentageChange($revenueLastMonth, $revenueThisMonth),
                'orders_total' => Order::count(),
                'orders_pending' => Order::where('status', Order::STATUS_PENDING)->count(),
                'orders_this_month' => Order::where('created_at', '>=', $thisMonth)->count(),
                'products_total' => Product::count(),
                'products_inactive' => Product::where('is_active', false)->count(),
                'messages_unhandled' => ContactMessage::whereNull('handled_at')->count(),
                'reviews_pending' => Review::where('is_approved', false)->count(),
                'waitlist_total' => WaitlistSignup::count(),
                'waitlist_waiting' => WaitlistSignup::whereNull('invited_at')->count(),
                'subscribers_total' => NewsletterSubscriber::whereNull('unsubscribed_at')->count(),
                'order_status_counts' => collect(Order::STATUSES)
                    ->mapWithKeys(fn ($status) => [
                        $status => Order::where('status', $status)->count(),
                    ])->all(),
                'best_sellers' => $this->bestSellers(),
                'recent_orders' => AdminOrderResource::collection(
                    Order::withCount('items')->latest()->limit(5)->get()
                ),
                'recent_messages' => ContactMessageResource::collection(
                    ContactMessage::latest()->limit(5)->get()
                ),
                'recent_signups' => WaitlistSignupResource::collection(
                    WaitlistSignup::latest()->limit(5)->get()
                ),
            ],
        ]);
    }

    private function revenueSince(\DateTimeInterface $from): int
    {
        return (int) Order::whereIn('status', Order::REVENUE_STATUSES)
            ->where('created_at', '>=', $from)
            ->sum('subtotal');
    }

    private function revenueBetween(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) Order::whereIn('status', Order::REVENUE_STATUSES)
            ->whereBetween('created_at', [$from, $to])
            ->sum('subtotal');
    }

    /**
     * Percentage movement, or null when there is no baseline to compare to.
     */
    private function percentageChange(int $previous, int $current): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Units sold per size, which on a one-product catalog is the figure that
     * actually tells the team what to brew next.
     *
     * @return list<array<string, mixed>>
     */
    private function bestSellers(): array
    {
        return \App\Models\OrderItem::query()
            ->selectRaw('product_name, product_slug, size, sum(qty) as units_sold, sum(line_total) as revenue')
            ->groupBy('product_name', 'product_slug', 'size')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->product_name,
                'slug' => $row->product_slug,
                'size' => $row->size,
                'units_sold' => (int) $row->units_sold,
                'revenue' => (int) $row->revenue,
            ])
            ->all();
    }
}
