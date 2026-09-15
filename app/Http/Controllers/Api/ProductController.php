<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * The catalog is a single ritual oil today, so it ships whole — the
     * storefront holds it in one context and resolves cart lines against it
     * without extra round trips.
     */
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(Product::query()->active()->ordered()->get());
    }

    public function show(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404);

        return new ProductResource($product);
    }
}
