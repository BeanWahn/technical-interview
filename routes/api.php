<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SecretsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Secrets API routes
Route::middleware('auth:sanctum')->group(function () {
    // Get all secrets for the authenticated user
    Route::get('/secret-content', [SecretsController::class, 'getUserSecrets']);

    // Get all secrets for a specific user (users can only access their own secrets)
    Route::get('/users/{userId}/secrets', [SecretsController::class, 'getSecretsForUser']);

    // CRUD routes for secrets
    Route::post('/secrets', [SecretsController::class, 'createSecret'])
        ->name('secrets.create');
    Route::put('/secrets/{secretId}', [SecretsController::class, 'updateSecret'])
        ->name('secrets.update');
    Route::delete('/secrets/{secretId}', [SecretsController::class, 'deleteSecret'])
        ->name('secrets.delete');

    // Secret sharing routes
    Route::post('/secrets/{secretId}/shares', [SecretsController::class, 'createShare']);
    Route::get('/secrets/{secretId}/shares', [SecretsController::class, 'getSecretShares']);
    Route::delete('/shares/{shareId}', [SecretsController::class, 'revokeShare']);
    Route::delete('/secrets/{secretId}/shares', [SecretsController::class, 'revokeAllShares']);

    Route::post('/generate-share-link', [\App\Http\Controllers\SecretsController::class, 'createShareLink'])
        ->name('generate-share-link');
});
