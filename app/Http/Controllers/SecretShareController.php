<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SecretShare;
use App\Models\Secret;

class SecretShareController extends Controller
{
    /**
     * Create a shareable link for a secret.
     */
    public function createShareLink(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $secretId = $request->input('secret_id');

        // Find the secret and ensure it belongs to the user
        $secret = Secret::where('id', $secretId)
                       ->where('user_id', $user->id)
                       ->first();

        if (!$secret) {
            return response()->json(['error' => 'Secret not found'], 404);
        }

        try {
            // Create the share
            $share = $secret->createShare();

            return response()->json([
                'share' => $share
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create share: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Access a shared secret via token.
     */
    public function accessSharedSecret(Request $request, string $token): JsonResponse
    {
        // Find the share by token
        $share = SecretShare::where('token', $token)->first();

        if (!$share) {
            return response()->json(['error' => 'Share link not found'], 404);
        }

        // Check if the share can still be accessed
        if (!$share->canBeAccessed()) {
            if ($share->is_expired) {
                return response()->json(['error' => 'This share link has expired'], 410);
            }

            if ($share->is_used) {
                return response()->json(['error' => 'This share link has already been used'], 410);
            }
            if ($share->is_disabled) {
                return response()->json(['error' => 'This share link has been disabled'], 410);
            }

            return response()->json(['error' => 'This share link is no longer accessible'], 410);
        }

        try {
            // Mark as accessed
            $share->markAsAccessed($request->ip());

            // Decrypt and return the content
            $content = $share->decryptSharedContent();

            return response()->json([
                'content' => $content,
                'shared_by' => $share->sharedByUser->name,
                'accessed_at' => $share->accessed_at->toISOString(),
                'expires_at' => $share->expires_at->toISOString(),
                'remaining_accesses' => 1 - $share->access_count
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to decrypt shared content'], 500);
        }
    }

    /**
     * Get all shares for a secret.
     */
    public function getSecretShares(Request $request, int $secretId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Find the secret and ensure it belongs to the user
        $secret = Secret::where('id', $secretId)
                       ->where('user_id', $user->id)
                       ->first();

        if (!$secret) {
            return response()->json(['error' => 'Secret not found'], 404);
        }

        $shares = $secret->shares()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'secret_id' => $secretId,
            'shares' => $shares,
            'count' => $shares->count()
        ]);
    }

    /**
     * Revoke a specific share.
     */
    public function revokeShare(Request $request, int $shareId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Find the share and ensure it belongs to the user
        $share = SecretShare::where('id', $shareId)
                           ->where('shared_by_user_id', $user->id)
                           ->first();

        if (!$share) {
            return response()->json(['error' => 'Share not found'], 404);
        }

        $share->update(['is_disabled' => true]);

        return response()->json(['message' => 'Share revoked successfully']);
    }

    /**
     * Re-enable a specific share.
     */
    public function reenableShare(Request $request, int $shareId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $share = SecretShare::where('id', $shareId)
                           ->where('shared_by_user_id', $user->id)
                           ->first();

        if (!$share) {
            return response()->json(['error' => 'Share not found'], 404);
        }

        $share->update(['is_disabled' => false]);

        return response()->json(['message' => 'Share re-enabled successfully']);
    }

    /**
     * Revoke all shares for a secret.
     */
    public function revokeAllShares(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Find the secret and ensure it belongs to the user
        $secret = Secret::where('id', $request->input('secret_id'))
                       ->where('user_id', $user->id)
                       ->first();

        if (!$secret) {
            return response()->json(['error' => 'Secret not found'], 404);
        }

        $secret->revokeAllShares();

        return response()->json(['message' => 'All shares revoked successfully']);
    }
}
