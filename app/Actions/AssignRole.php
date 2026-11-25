<?php

namespace App\Actions;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place roles are changed. Only a super-admin may assign roles.
 */
class AssignRole
{
    public const ROLES = ['super-admin', 'branch-admin', 'member'];

    /**
     * @throws AuthorizationException
     */
    public function handle(User $actor, User $target, string $role): User
    {
        if (! $actor->hasRole('super-admin')) {
            throw new AuthorizationException('Only a super-admin can assign roles.');
        }

        if (! in_array($role, self::ROLES, true)) {
            throw new AuthorizationException('Unknown role.');
        }

        if ($role === 'branch-admin' && $target->branch_id === null) {
            throw ValidationException::withMessages([
                'role' => 'Assign the user to a branch before giving this role.',
            ]);
        }

        DB::transaction(function () use ($target, $role) {
            $superAdmins = User::role('super-admin')->lockForUpdate()->get();

            if (
                $role !== 'super-admin'
                && $superAdmins->contains('id', $target->id)
                && $superAdmins->count() <= 1
            ) {
                throw new AuthorizationException('Cannot remove the last super-admin.');
            }

            if ($role === 'branch-admin') {
                $this->assertAdminSlotFree($target, 'role');
            }

            $target->syncRoles([$role]);
        });

        return $target;
    }

    /** Max branch-admins for a branch, from config/ministry.php (by slug, else the default). */
    public static function adminLimit(Branch $branch): int
    {
        return (int) config("ministry.branch_admin_limit.branches.{$branch->slug}", config('ministry.branch_admin_limit.default', 1));
    }

    /**
     * Fails when the target's branch already has its full set of branch-admins (not counting
     * the target). Call inside a transaction: the branch row is locked so two requests
     * cannot both take the last slot.
     *
     * @throws ValidationException
     */
    public function assertAdminSlotFree(User $target, string $field): void
    {
        $branch = Branch::query()->lockForUpdate()->find($target->branch_id);

        if ($branch === null) {
            return;
        }

        $limit = self::adminLimit($branch);
        $current = User::role('branch-admin')
            ->where('branch_id', $branch->id)
            ->whereKeyNot($target->id)
            ->lockForUpdate()
            ->get(['users.id'])
            ->count();

        if ($current >= $limit) {
            $admins = $limit === 1 ? 'branch admin' : 'branch admins';

            throw ValidationException::withMessages([
                $field => "{$branch->name} already has its {$limit} {$admins}. Remove that role first or raise the limit.",
            ]);
        }
    }
}
