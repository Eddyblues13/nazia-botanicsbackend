<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Shopper accounts.
 *
 * Separate from the staff controller and issuing tokens on the `customer`
 * guard, so a shopper's token is never accepted by the dashboard.
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($data);

        return response()->json([
            'token' => $user->createToken('storefront')->plainTextToken,
            'user' => $this->present($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        // One message for both cases, so this cannot be used to discover which
        // addresses have accounts.
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Those details do not match an account.',
            ]);
        }

        return response()->json([
            'token' => $user->createToken('storefront')->plainTextToken,
            'user' => $this->present($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->present($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Only the token in hand, so signing out on a phone leaves a laptop
        // signed in.
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'email', 'max:180',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated.',
            'data' => $this->present($user->fresh()),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'That is not your current password.',
            ]);
        }

        $user->update(['password' => $data['password']]);

        // Every other session is dropped, in case the password was changed
        // because someone else knew it.
        $current = $user->currentAccessToken();
        $user->tokens()->whereKeyNot($current->getKey())->delete();

        return response()->json(['message' => 'Password updated.']);
    }

    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
