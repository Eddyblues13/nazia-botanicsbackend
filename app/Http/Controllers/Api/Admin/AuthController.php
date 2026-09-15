<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Resources\AdminResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $admin = $request->authenticateAdmin();

        $admin->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $admin->createToken('admin-dashboard')->plainTextToken,
            'admin' => new AdminResource($admin),
        ]);
    }

    public function me(Request $request): AdminResource
    {
        return new AdminResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    /**
     * Changing a password invalidates every other session, keeping a stolen
     * token from outliving the password it was issued against.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = $request->user();

        if (! Hash::check($data['current_password'], $admin->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'That is not your current password.',
            ]);
        }

        $admin->update(['password' => $data['password']]);

        $currentToken = $admin->currentAccessToken();
        $admin->tokens()->whereKeyNot($currentToken->getKey())->delete();

        return response()->json(['message' => 'Password updated.']);
    }
}
