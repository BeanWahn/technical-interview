<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Secret;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Illuminate\Contracts\Support\Responsable;
use App\Http\Controllers\Concerns\AuthorizationHandler;
use Illuminate\Support\Facades\Log;

class SecretsController extends Controller
{
    use AuthorizationHandler;
    public function index(): Responsable
    {
        return Inertia::render('Secrets');
    }

    /**
     * Get all secrets for the authenticated user.
     */
    public function getUserSecrets(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        // Get all secrets for the authenticated user
        $secrets = $user->secrets()
            ->orderBy('created_at', 'desc')
            ->with('shares')
            ->get()
            ->map(function ($secret) {
                return [
                    'id' => $secret->id,
                    'user_id' => $secret->user_id,
                    'is_encrypted' => $secret->is_encrypted,
                    'encrypted_content' => $secret->getEncryptedContent(),
                    'decrypted_content' => $secret->getDecryptedContent(),
                    'created_at' => $secret->created_at,
                    'updated_at' => $secret->updated_at,
                    'shares' => $secret->shares
                ];
            });

        return response()->json($secrets);
    }

    public function createSecret(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        // Encrypt content using user's encryption key
        $content = $request->input('content');
        $encryptedContent = $user->encryptContent($content);

        $secret = $user->secrets()->create([
            'content' => $encryptedContent,
            'is_encrypted' => true
        ])->load('shares');

        // Return both encrypted and decrypted content
        $secretData = [
            'id' => $secret->id,
            'user_id' => $secret->user_id,
            'is_encrypted' => $secret->is_encrypted,
            'encrypted_content' => $secret->getEncryptedContent(),
            'decrypted_content' => $secret->getDecryptedContent(),
            'created_at' => $secret->created_at,
            'updated_at' => $secret->updated_at,
            'shares' => $secret->shares
        ];

        return response()->json($secretData);
    }

    public function updateSecret(Request $request, int $secretId): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $secret = $this->findUserSecret($user, $secretId);

        if (!$secret) {
            return $this->secretNotFoundResponse();
        }

        // Update the secret content
        $secret->update(['content' => $request->input('content')]);

        // Update all active share links with the new content
        try {
            $updatedSharesCount = $secret->updateActiveShares();

            return response()->json([
                'message' => 'Secret updated successfully',
                'updated_shares' => $updatedSharesCount
            ]);
        } catch (\Exception $e) {
            // Log the error but don't fail the secret update
            Log::error('Failed to update share links after secret edit', [
                'secret_id' => $secretId,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Secret updated successfully, but some share links may not reflect the changes',
                'updated_shares' => 0
            ]);
        }
    }

    public function deleteSecret(Request $request, int $secretId): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $secret = $this->findUserSecret($user, $secretId);

        if (!$secret) {
            return $this->secretNotFoundResponse();
        }

        $secret->delete();

        return response()->json(['message' => 'Secret deleted successfully']);
    }
}
