<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWaitlistSignupRequest;
use App\Models\WaitlistSignup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class WaitlistController extends Controller
{
    /**
     * Signing up twice updates the existing row rather than erroring — from
     * the visitor's side, joining a list they are already on should just work.
     */
    public function store(StoreWaitlistSignupRequest $request): JsonResponse
    {
        $data = $request->validated();

        WaitlistSignup::updateOrCreate(
            ['email' => Str::lower($data['email'])],
            ['phone' => $data['phone'] ?? null],
        );

        return response()->json([
            'message' => "You're on the list — we'll reach out as soon as the next batch is ready.",
        ], 201);
    }
}
