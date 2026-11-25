# Remove branch-head role
Date: 2026-11-26

Owner decision during the full admin review: remove the `branch-head` role. Each branch has 1 `branch-admin` (COG Dasma has 2), with the powers branch-heads had. Limit enforced in code, set in `config/ministry.php`.

Why: branch-admins saw Gallery, Team and Users but could not act there, which went against [[admins-equal-per-branch]]. One admin level is simpler for a small ministry.

Demo accounts: `head@cogdasma.audio` becomes Dasma's 2nd branch-admin; the other `head@` accounts are removed (owner approved; [[Demo-Accounts]] updated when built).

Supersedes: the branch-head parts of [[admins-equal-per-branch]] and "branch-admin stays read-only" in [[admin-user-management]].
Spec: [[remove-branch-head]].
