<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Secret;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Illuminate\Contracts\Support\Responsable;
use App\Http\Controllers\Concerns\AuthorizationHandler;

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
            ->get();

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

        return response()->json($secret);
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

        $secret->update(['content' => $request->input('content')]);

        return response()->json(['message' => 'Secret updated successfully']);
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
