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
        ])->load('shares');

        return response()->json($secret);
    }

    public function updateSecret(Request $request, int $secretId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $secret = Secret::where('id', $secretId)
                       ->where('user_id', $user->id)
                       ->first();

        if (!$secret) {
            return response()->json(['error' => 'Secret not found'], 404);
        }

        $secret->update(['content' => $request->input('content')]);

        return response()->json(['message' => 'Secret updated successfully']);
    }

    public function deleteSecret(Request $request, int $secretId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $secret = Secret::where('id', $secretId)
                       ->where('user_id', $user->id)
                       ->first();

        if (!$secret) {
            return response()->json(['error' => 'Secret not found'], 404);
        }

        $secret->delete();

        return response()->json(['message' => 'Secret deleted successfully']);
    }
}
