<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\QueuedResetPassword;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function dutyAssignments(): HasMany
    {
        return $this->hasMany(DutyAssignment::class);
    }

    public function unavailabilities(): HasMany
    {
        return $this->hasMany(MemberUnavailability::class);
    }

    /**
     * Accounts that need an admin's eye: temporary password, invite not accepted yet,
     * and (super-admin view only) no branch. Super-admins have no branch by design.
     */
    public function scopeNeedsAttention(Builder $query, bool $includeNoBranch = false): Builder
    {
        return $query->where(function (Builder $q) use ($includeNoBranch) {
            $q->where('must_change_password', true)->orWhereNull('email_verified_at');

            if ($includeNoBranch) {
                $q->orWhere(fn (Builder $q) => $q->whereNull('branch_id')->withoutRole('super-admin'));
            }
        });
    }

    /** First matching attention reason, for display. */
    public function attentionReason(): string
    {
        return match (true) {
            (bool) $this->must_change_password => 'Still on temporary password',
            $this->email_verified_at === null => 'Invite not accepted yet',
            default => 'No branch',
        };
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
        return $this->hasAdminAccess();
    }

    /** Admins manage the roster; they never serve duties. */
    public function isAdminRole(): bool
    {
        return $this->hasAnyRole(['branch-admin', 'super-admin']);
    }

    /** Same rule as canAccessPanel, usable without a Panel instance. */
    public function hasAdminAccess(): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        // Branch admins need a branch; an admin without one gets nothing.
        return $this->branch_id !== null && $this->hasRole('branch-admin');
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
            'email_notifications' => 'boolean',
        ];
    }
}
