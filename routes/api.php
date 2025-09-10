<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SecretsController;
use App\Http\Controllers\SecretShareController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Secrets API routes
Route::middleware('auth:sanctum')->group(function () {
    // Get all secrets for the authenticated user
    Route::get('/secret-content', [SecretsController::class, 'getUserSecrets']);

    // Create, update and delete secrets
    Route::post('/secrets', [SecretsController::class, 'createSecret'])
        ->name('secrets.create');
    Route::put('/secrets/{secretId}', [SecretsController::class, 'updateSecret'])
        ->name('secrets.update');
    Route::delete('/secrets/{secretId}', [SecretsController::class, 'deleteSecret'])
        ->name('secrets.delete');

    // Create, get and revoke share links
    Route::post('/secrets/{secretId}/shares', [SecretShareController::class, 'createShareLink']);
    Route::get('/secrets/{secretId}/shares', [SecretShareController::class, 'getSecretShares']);
    Route::put('/shares/{shareId}/revoke', [SecretShareController::class, 'revokeShare']);
    Route::put('/shares/{shareId}/reenable', [SecretShareController::class, 'reenableShare']);
});
