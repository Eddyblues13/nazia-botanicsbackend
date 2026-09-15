<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\WaitlistSignupResource;
use App\Models\WaitlistSignup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WaitlistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:invited,waiting'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $signups = WaitlistSignup::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($q) => $q->where('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $status === 'invited'
                ? $query->whereNotNull('invited_at')
                : $query->whereNull('invited_at'))
            ->latest()
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return WaitlistSignupResource::collection($signups)->additional([
            'meta' => [
                'waiting_count' => WaitlistSignup::whereNull('invited_at')->count(),
                'total_count' => WaitlistSignup::count(),
            ],
        ]);
    }

    /**
     * Marks someone as invited once the batch email has gone out, so the team
     * can tell who still needs contacting.
     */
    public function toggleInvited(WaitlistSignup $signup): WaitlistSignupResource
    {
        $signup->update([
            'invited_at' => $signup->invited_at ? null : now(),
        ]);

        return new WaitlistSignupResource($signup);
    }

    public function destroy(WaitlistSignup $signup): JsonResponse
    {
        $signup->delete();

        return response()->json(['message' => 'Signup removed.']);
    }

    /**
     * Streamed so the whole list never has to sit in memory at once — this is
     * the file the team pastes into their mail provider.
     */
    public function export(): StreamedResponse
    {
        $filename = 'nazia-waitlist-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Phone', 'Joined', 'Invited']);

            WaitlistSignup::query()->oldest()->chunk(500, function ($signups) use ($out) {
                foreach ($signups as $signup) {
                    fputcsv($out, [
                        $signup->email,
                        $signup->phone,
                        $signup->created_at?->toDateTimeString(),
                        $signup->invited_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
