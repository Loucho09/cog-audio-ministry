<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\QueuedResetPassword;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Stale-token hygiene: a pending reset/invite link must not outlive a password
     * change, an email change, or the account itself (a re-created account with the
     * same email must not inherit an old link).
     */
    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged('password')) {
                self::forgetResetTokens($user->email);
            }

            if ($user->wasChanged('email')) {
                self::forgetResetTokens((string) $user->getOriginal('email'));
            }
        });

        static::deleted(fn (User $user) => self::forgetResetTokens($user->email));
    }

    private static function forgetResetTokens(string $email): void
    {
        DB::table(config('auth.passwords.users.table'))->where('email', $email)->delete();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Only plain members (or accounts with no role) may delete their own account.
     */
    public function canSelfDelete(): bool
    {
        return $this->getRoleNames()->diff(['member'])->isEmpty();
    }

    public function sendPasswordResetNotification(#[SensitiveParameter] $token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        // Branch admins need a branch; an admin without one gets nothing.
        return $this->branch_id !== null && $this->hasAnyRole(['branch-head', 'branch-admin']);
    }

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
            'must_change_password' => 'boolean',
        ];
    }
}
