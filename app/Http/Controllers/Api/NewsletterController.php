<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsletterSubscriberRequest;
use App\Mail\NewsletterWelcome;
use App\Models\NewsletterSubscriber;
use App\Support\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function store(StoreNewsletterSubscriberRequest $request): JsonResponse
    {
        $data = $request->validated();

        $subscriber = NewsletterSubscriber::updateOrCreate(
            ['email' => Str::lower($data['email'])],
            [
                'source' => $data['source'] ?? 'popup',
                // Re-subscribing clears an earlier opt-out.
                'unsubscribed_at' => null,
            ],
        );

        // Only greet a genuinely new subscriber — re-submitting the form should
        // not send someone the welcome a second time.
        if ($subscriber->wasRecentlyCreated) {
            Notifier::send($subscriber->email, new NewsletterWelcome($subscriber), 'newsletter welcome');
        }

        return response()->json([
            'message' => 'Thank you for joining our community.',
        ], 201);
    }
}
