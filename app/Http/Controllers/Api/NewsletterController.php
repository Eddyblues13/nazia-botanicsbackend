<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsletterSubscriberRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function store(StoreNewsletterSubscriberRequest $request): JsonResponse
    {
        $data = $request->validated();

        NewsletterSubscriber::updateOrCreate(
            ['email' => Str::lower($data['email'])],
            [
                'source' => $data['source'] ?? 'popup',
                // Re-subscribing clears an earlier opt-out.
                'unsubscribed_at' => null,
            ],
        );

        return response()->json([
            'message' => 'Thank you for joining our community.',
        ], 201);
    }
}
