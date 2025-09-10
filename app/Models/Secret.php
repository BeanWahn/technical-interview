<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class Secret extends Model
{
    use HasFactory;

    protected $fillable = ['content', 'user_id', 'is_encrypted'];

    /**
     * Get the decrypted content attribute.
     */
    public function getContentAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            try {
                // Ensure user relationship is loaded
                $this->ensureUserLoaded();

                // Use user-specific encryption if user exists
                if ($this->user) {
                    return $this->user->decryptContent($value);
                } else {
                    // Fallback to global encryption for backward compatibility
                    return Crypt::decryptString($value);
                }
            } catch (DecryptException $e) {
                // If decryption fails, return the original value
                return $value;
            } catch (\Exception $e) {
                // If user-specific decryption fails, return the original value
                return $value;
            }
        }

        return $value;
    }

    /**
     * Set the content attribute with automatic encryption.
     */
    public function setContentAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            // Ensure user relationship is loaded
            $this->ensureUserLoaded();

            // Use user-specific encryption if user exists
            if ($this->user) {
                $this->attributes['content'] = $this->user->encryptContent($value);
            } else {
                // Fallback to global encryption for backward compatibility
                $this->attributes['content'] = Crypt::encryptString($value);
            }
        } else {
            $this->attributes['content'] = $value;
        }
    }

    /**
     * Manually encrypt content using user's encryption key.
     */
    public function encryptContent($content)
    {
        if ($this->user) {
            return $this->user->encryptContent($content);
        }
        return Crypt::encryptString($content);
    }

    /**
     * Manually decrypt content using user's encryption key.
     */
    public function decryptContent($encryptedContent)
    {
        if ($this->user) {
            return $this->user->decryptContent($encryptedContent);
        }

        try {
            return Crypt::decryptString($encryptedContent);
        } catch (DecryptException $e) {
            throw new \Exception('Failed to decrypt content: ' . $e->getMessage());
        }
    }


    /**
     * Ensure user relationship is loaded for encryption operations.
     */
    protected function ensureUserLoaded()
    {
        if (!$this->relationLoaded('user')) {
            $this->load('user');
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all shares for this secret.
     */
    public function shares()
    {
        return $this->hasMany(SecretShare::class);
    }

    /**
     * Create a shareable link for this secret.
     */
    public function createShare($options = [])
    {
        // Ensure user relationship is loaded
        $this->ensureUserLoaded();

        if (!$this->user) {
            throw new \Exception('Cannot share secret without user relationship');
        }

        // Generate unique token and sharing key
        $token = SecretShare::generateToken();
        $sharingKey = SecretShare::generateSharingKey();

        // Decrypt the secret content using user's key
        $decryptedContent = $this->content;

        // Re-encrypt with the sharing key
        $encryptedForSharing = SecretShare::encryptWithSharingKey($decryptedContent, $sharingKey);

        // Create the share record
        $share = SecretShare::create([
            'token' => $token,
            'secret_id' => $this->id,
            'shared_by_user_id' => $this->user->id,
            'encrypted_content' => $encryptedForSharing,
            'sharing_key' => $sharingKey,
            'access_count' => 0,
            'is_used' => false,
            'expires_at' => now()->addHours(24)
        ]);

        return $share;
    }

    /**
     * Get the shareable URL for a given share.
     */
    public function getShareUrl($share)
    {
        return url("/shared-secret/{$share->token}");
    }

    /**
     * Revoke all active shares for this secret.
     */
    public function revokeAllShares()
    {
        $this->shares()->active()->update(['is_disabled' => true]);
    }

}
