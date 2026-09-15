<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactAlert;
use App\Models\ContactMessage;
use App\Support\Notifier;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $message = ContactMessage::create($request->validated());

        Notifier::send(Notifier::team(), new ContactAlert($message), 'contact alert');

        return response()->json([
            'message' => "Message received — we reply to every message within one business day.",
        ], 201);
    }
}
