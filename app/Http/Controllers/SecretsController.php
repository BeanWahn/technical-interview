<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Secret;
use App\Models\SecretShare;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Illuminate\Contracts\Support\Responsable;

class SecretsController extends Controller
{
    public function index(): Responsable
    {
        return Inertia::render('Secrets');
    }

    /**
     * Get all secrets for the authenticated user.
     */
    public function getUserSecrets(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get all secrets for the authenticated user
        $secrets = $user->secrets()
            ->orderBy('created_at', 'desc')
            ->with('shares')
            ->get();

        return response()->json($secrets);
    }

    /**
     * Get all secrets for a specific user (admin functionality).
     */
    public function getSecretsForUser(Request $request, int $userId): JsonResponse
    {
        $authenticatedUser = $request->user();

        if (!$authenticatedUser) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if the user exists first
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // For now, only allow users to access their own secrets
        // You can modify this to add admin permissions later
        if ($authenticatedUser->id !== $userId) {
            return response()->json(['error' => 'Forbidden - You can only access your own secrets'], 403);
        }

        $secrets = $user->secrets()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'user_id' => $userId,
            'secrets' => $secrets,
            'count' => $secrets->count()
        ]);
    }

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
            $share = $secret->createShare([
                'expires_in_hours' => 24, // 24 hours default
                'max_access_count' => 1, // 1 access
                'notes' => null // No notes for now
            ]);

            return response()->json([
                'share' => [
                    'id' => $share->id,
                    'token' => $share->token,
                    'url' => $secret->getShareUrl($share),
                    'expires_at' => $share->expires_at->toISOString(),
                    'max_access_count' => $share->max_access_count,
                    'notes' => $share->notes,
                    'created_at' => $share->created_at->toISOString()
                ]
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
            return response()->json(['error' => 'Share not found'], 404);
        }

        // Check if the share can still be accessed
        if (!$share->canBeAccessed()) {
            if ($share->isExpired()) {
                return response()->json(['error' => 'This share has expired'], 410);
            }

            if ($share->is_used) {
                return response()->json(['error' => 'This share has already been used'], 410);
            }

            return response()->json(['error' => 'This share is no longer accessible'], 410);
        }

        try {
            // Mark as accessed
            $share->markAsAccessed($request->ip());

            // Decrypt and return the content
            $content = $share->decryptSharedContent();

            return response()->json([
                'content' => $content,
                'shared_by' => $share->sharedByUser->name,
                'notes' => $share->notes,
                'accessed_at' => $share->accessed_at->toISOString(),
                'expires_at' => $share->expires_at->toISOString(),
                'remaining_accesses' => $share->max_access_count - $share->access_count
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
            ->get()
            ->map(function ($share) use ($secret) {
                return [
                    'id' => $share->id,
                    'token' => $share->token,
                    'url' => $secret->getShareUrl($share),
                    'expires_at' => $share->expires_at->toISOString(),
                    'max_access_count' => $share->max_access_count,
                    'access_count' => $share->access_count,
                    'is_used' => $share->is_used,
                    'is_expired' => $share->isExpired(),
                    'can_be_accessed' => $share->canBeAccessed(),
                    'notes' => $share->notes,
                    'accessed_at' => $share->accessed_at?->toISOString(),
                    'accessed_ip' => $share->accessed_ip,
                    'created_at' => $share->created_at->toISOString()
                ];
            });

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

        $share->update(['is_used' => true]);

        return response()->json(['message' => 'Share revoked successfully']);
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

    public function createSecret(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Encrypt content using user's encryption key
        $content = $request->input('content');
        $encryptedContent = $user->encryptContent($content);

        $secret = $user->secrets()->create([
            'content' => $encryptedContent,
            'is_encrypted' => true
        ]);

        return response()->json(['secret' => $secret]);
    }
}
