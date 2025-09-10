<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class SecretShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'secret_id',
        'shared_by_user_id',
        'encrypted_content',
        'sharing_key',
        'expires_at',
        'accessed_at',
        'accessed_ip',
        'access_count',
        'is_used',
        'is_disabled'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accessed_at' => 'datetime',
        'is_used' => 'boolean',
        'is_disabled' => 'boolean',
        'access_count' => 'integer'
    ];

    protected $hidden = [
        'sharing_key',
        'encrypted_content'
    ];
    protected $appends = ['url', 'is_expired'];

    /**
     * Get the secret that is being shared.
     */
    public function secret()
    {
        return $this->belongsTo(Secret::class);
    }

    /**
     * Get the user who shared the secret.
     */
    public function sharedByUser()
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    /**
     * Check if the share is expired.
     */
    public function getIsExpiredAttribute()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the share is disabled.
     */
    public function isDisabled()
    {
        return $this->is_disabled;
    }

    /**
     * Check if the share can still be accessed.
     */
    public function canBeAccessed()
    {
        return !$this->is_expired &&
               !$this->is_used &&
               !$this->is_disabled &&
               $this->access_count < 1;
    }

    /**
     * Mark the share as accessed.
     */
    public function markAsAccessed($ip = null)
    {
        $this->update([
            'accessed_at' => now(),
            'accessed_ip' => $ip,
            'access_count' => $this->access_count + 1,
            'is_used' => $this->access_count + 1 >= 1
        ]);
    }

    /**
     * Decrypt the shared content using the sharing key.
     */
    public function decryptSharedContent()
    {
        try {
            $data = base64_decode($this->encrypted_content);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->sharing_key, 0, $iv);

            if ($decrypted === false) {
                throw new \Exception('Failed to decrypt shared content');
            }

            return $decrypted;
        } catch (\Exception $e) {
            throw new \Exception('Failed to decrypt shared content: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique sharing token.
     */
    public static function generateToken()
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Generate a sharing key.
     */
    public static function generateSharingKey()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Encrypt content with a sharing key.
     */
    public static function encryptWithSharingKey($content, $sharingKey)
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($content, 'AES-256-CBC', $sharingKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Scope to get only active (non-expired, non-used, non-disabled) shares.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
                    ->where('is_used', false)
                    ->where('is_disabled', false)
                    ->where('access_count', '<', 1);
    }

    /**
     * Scope to get expired shares.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to get used shares.
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    /**
     * Scope to get disabled shares.
     */
    public function scopeDisabled($query)
    {
        return $query->where('is_disabled', true);
    }

    /**
     * Get the full share URL for this secret share.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        $appUrl = rtrim(config('app.url'), '/');
        return $appUrl . '/shared-secret/' . $this->token;
    }
}
