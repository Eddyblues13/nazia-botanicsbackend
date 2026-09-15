<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsletterSubscriberResource;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'in:popup,footer,waitlist'],
            'status' => ['nullable', 'in:subscribed,unsubscribed'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $subscribers = NewsletterSubscriber::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('email', 'like', "%{$search}%"))
            ->when($filters['source'] ?? null, fn ($query, $source) => $query->where('source', $source))
            ->when($filters['status'] ?? null, fn ($query, $status) => $status === 'subscribed'
                ? $query->whereNull('unsubscribed_at')
                : $query->whereNotNull('unsubscribed_at'))
            ->latest()
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return NewsletterSubscriberResource::collection($subscribers)->additional([
            'meta' => [
                'subscribed_count' => NewsletterSubscriber::whereNull('unsubscribed_at')->count(),
                'total_count' => NewsletterSubscriber::count(),
            ],
        ]);
    }

    public function destroy(NewsletterSubscriber $subscriber): JsonResponse
    {
        $subscriber->delete();

        return response()->json(['message' => 'Subscriber removed.']);
    }

    /**
     * Only live subscribers are exported — anyone who opted out stays out of
     * the file the team sends from.
     */
    public function export(): StreamedResponse
    {
        $filename = 'nazia-newsletter-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Source', 'Subscribed']);

            NewsletterSubscriber::query()
                ->whereNull('unsubscribed_at')
                ->oldest()
                ->chunk(500, function ($subscribers) use ($out) {
                    foreach ($subscribers as $subscriber) {
                        fputcsv($out, [
                            $subscriber->email,
                            $subscriber->source,
                            $subscriber->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
