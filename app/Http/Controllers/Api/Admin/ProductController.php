<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Resources\AdminProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Unlike the storefront index, this lists inactive products too.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $products = Product::query()
            ->withSum('orderItems as order_items_sum_qty', 'qty')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%")
            ))
            ->when(isset($filters['status']), fn ($query) => $query->where(
                'is_active',
                $filters['status'] === 'active'
            ))
            ->ordered()
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return AdminProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($this->payload($request));

        return (new AdminProductResource($product))->response()->setStatusCode(201);
    }

    public function show(Product $product): AdminProductResource
    {
        $product->loadSum('orderItems as order_items_sum_qty', 'qty');

        return new AdminProductResource($product);
    }

    public function update(StoreProductRequest $request, Product $product): AdminProductResource
    {
        $product->update($this->payload($request, $product));

        return new AdminProductResource($product->fresh());
    }

    /**
     * Products that have already been ordered are deactivated rather than
     * deleted, so historical orders keep their link to the catalog.
     */
    public function destroy(Product $product): JsonResponse
    {
        if ($product->orderItems()->exists()) {
            $product->update(['is_active' => false]);

            return response()->json([
                'message' => 'This oil appears in past orders, so it was hidden from the shop instead of deleted.',
                'deactivated' => true,
            ]);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted.', 'deactivated' => false]);
    }

    public function toggle(Product $product): AdminProductResource
    {
        $product->update(['is_active' => ! $product->is_active]);
        $product->loadSum('orderItems as order_items_sum_qty', 'qty');

        return new AdminProductResource($product);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StoreProductRequest $request, ?Product $product = null): array
    {
        $data = $request->validated();

        $data['slug'] = $data['slug']
            ?? $product?->slug
            ?? $this->uniqueSlug($data['name']);

        $data['highlights'] = $data['highlights'] ?? [];
        $data['ingredients'] = $data['ingredients'] ?? [];
        $data['is_active'] = $data['is_active'] ?? true;
        $data['position'] = $data['position'] ?? (Product::max('position') + 1);

        // The "from" price is always the cheapest size, so it can never drift
        // out of step with what the sizes actually cost.
        $data['price_from'] = collect($data['sizes'])->min('price');

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
