<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ReviewResource::collection(Review::query()->approved()->ordered()->get());
    }

    /**
     * Submissions are held unapproved until someone on the team publishes
     * them, so the homepage can never be defaced from the open form.
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        Review::create([
            ...$request->validated(),
            'is_approved' => false,
        ]);

        return response()->json([
            'message' => 'Thank you — your review will appear once we have read it.',
        ], 201);
    }
}
