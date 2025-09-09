<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'encryption_key',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'encryption_key',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the secrets for the user.
     */
    public function secrets()
    {
        return $this->hasMany(Secret::class);
    }

    /**
     * Generate a unique encryption key for the user.
     */
    public function generateEncryptionKey()
    {
        $this->encryption_key = \Illuminate\Support\Str::random(32);
        $this->save();
        return $this->encryption_key;
    }

    /**
     * Get the user's encryption key.
     */
    public function getEncryptionKey()
    {
        if (!$this->encryption_key) {
            $this->generateEncryptionKey();
        }
        return $this->encryption_key;
    }

    /**
     * Check if user has an encryption key.
     */
    public function hasEncryptionKey()
    {
        return !empty($this->encryption_key);
    }

    /**
     * Encrypt content using the user's specific encryption key.
     */
    public function encryptContent($content)
    {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($content, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt content using the user's specific encryption key.
     */
    public function decryptContent($encryptedContent)
    {
        $key = $this->getEncryptionKey();
        try {
            $data = base64_decode($encryptedContent);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);

            if ($decrypted === false) {
                throw new \Exception('Failed to decrypt content');
            }

            return $decrypted;
        } catch (\Exception $e) {
            throw new \Exception('Failed to decrypt content: ' . $e->getMessage());
        }
    }
}
