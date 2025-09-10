<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

trait AuthorizationHandler
{
    /**
     * Get the authenticated user or return unauthorized response.
     */
    protected function getAuthenticatedUser(Request $request): ?\App\Models\User
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        return $user;
    }

    /**
     * Return unauthorized response.
     */
    protected function unauthorizedResponse(): JsonResponse
    {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Find a secret belonging to the user or return not found response.
     */
    protected function findUserSecret(\App\Models\User $user, int $secretId): ?\App\Models\Secret
    {
        return \App\Models\Secret::where('id', $secretId)
                                 ->where('user_id', $user->id)
                                 ->first();
    }

    /**
     * Return secret not found response.
     */
    protected function secretNotFoundResponse(): JsonResponse
    {
        return response()->json(['error' => 'Secret not found'], 404);
    }

    /**
     * Find a share belonging to the user or return not found response.
     */
    protected function findUserShare(\App\Models\User $user, int $shareId): ?\App\Models\SecretShare
    {
        return \App\Models\SecretShare::where('id', $shareId)
                                      ->where('shared_by_user_id', $user->id)
                                      ->first();
    }

    /**
     * Return share not found response.
     */
    protected function shareNotFoundResponse(): JsonResponse
    {
        return response()->json(['error' => 'Share not found'], 404);
    }
}
