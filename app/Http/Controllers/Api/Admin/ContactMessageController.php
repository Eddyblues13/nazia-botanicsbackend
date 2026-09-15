<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactMessageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:handled,unhandled'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $messages = ContactMessage::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $status === 'handled'
                ? $query->whereNotNull('handled_at')
                : $query->whereNull('handled_at'))
            ->latest()
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return ContactMessageResource::collection($messages)->additional([
            'meta' => [
                'unhandled_count' => ContactMessage::whereNull('handled_at')->count(),
            ],
        ]);
    }

    public function show(ContactMessage $message): ContactMessageResource
    {
        return new ContactMessageResource($message);
    }

    public function toggleHandled(ContactMessage $message): ContactMessageResource
    {
        $message->update([
            'handled_at' => $message->handled_at ? null : now(),
        ]);

        return new ContactMessageResource($message);
    }

    public function destroy(ContactMessage $message): JsonResponse
    {
        $message->delete();

        return response()->json(['message' => 'Message deleted.']);
    }
}
