<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SecretShare;
use App\Http\Controllers\Concerns\AuthorizationHandler;

class SecretShareController extends Controller
{
    use AuthorizationHandler;
    /**
     * Create a shareable link for a secret.
     */
    public function createShareLink(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $secretId = $request->input('secret_id');

        // Find the secret and ensure it belongs to the user
        $secret = $this->findUserSecret($user, $secretId);

        if (!$secret) {
            return $this->secretNotFoundResponse();
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
                'accessed_at' => $share->accessed_at->toISOString()
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
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        // Find the secret and ensure it belongs to the user
        $secret = $this->findUserSecret($user, $secretId);

        if (!$secret) {
            return $this->secretNotFoundResponse();
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
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        // Find the share and ensure it belongs to the user
        $share = $this->findUserShare($user, $shareId);

        if (!$share) {
            return $this->shareNotFoundResponse();
        }

        $share->update(['is_disabled' => true]);

        return response()->json(['message' => 'Share revoked successfully']);
    }

    /**
     * Re-enable a specific share.
     */
    public function reenableShare(Request $request, int $shareId): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $share = $this->findUserShare($user, $shareId);

        if (!$share) {
            return $this->shareNotFoundResponse();
        }

        $share->update(['is_disabled' => false]);

        return response()->json(['message' => 'Share re-enabled successfully']);
    }

}
