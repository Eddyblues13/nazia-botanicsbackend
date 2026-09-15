<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:approved,pending'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $reviews = Review::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('text', 'like', "%{$search}%")
            ))
            ->when(isset($filters['status']), fn ($query) => $query->where(
                'is_approved',
                $filters['status'] === 'approved'
            ))
            ->latest()
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return ReviewResource::collection($reviews)->additional([
            'meta' => [
                'pending_count' => Review::where('is_approved', false)->count(),
            ],
        ]);
    }

    public function toggle(Review $review): ReviewResource
    {
        $review->update(['is_approved' => ! $review->is_approved]);

        return new ReviewResource($review);
    }

    public function destroy(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json(['message' => 'Review deleted.']);
    }
}
