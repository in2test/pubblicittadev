<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Override;

/**
 * User Model
 *
 * Represents a user of the platform. Users can be either 'admins' (who manage
 * the catalog and quotes) or 'clients' (who browse products and request quotes).
 */
#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public const ROLE_CLIENT = 'client';

    public const ROLE_ADMIN = 'admin';

    /**
     * Get all the quotes associated with the user.
     *
     * @return HasMany
     */
    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Get all the addresses associated with the user.
     *
     * @return HasMany
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get all the orders associated with the user.
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if the user has administrative privileges.
     *
     * @return bool True if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if the user account is currently active.
     *
     * @return bool True if the account is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Determine if the user can access the Filament admin panel.
     *
     * Access is granted only if the user is both an administrator and active.
     *
     * @return bool True if access is granted.
     */
    public function canAccessFilament(): bool
    {
        return $this->isAdmin() && $this->isActive();
    }

    /**
     * Generate the user's initials for UI display (e.g., in avatars).
     *
     * @return string The first letter of the first two words of the name.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
