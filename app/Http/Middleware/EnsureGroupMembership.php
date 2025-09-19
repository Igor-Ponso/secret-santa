<?php

namespace App\Http\Middleware;

use App\Models\Group;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * Ensure the authenticated user is a participant (owner or accepted invite) of the group.
 * If not, we intentionally throw a ModelNotFoundException to return a 404, avoiding
 * leaking the existence of the resource via 403 responses.
 */
class EnsureGroupMembership
{
    public function handle(Request $request, Closure $next)
    {
        $group = $request->route('group');

        // Only act if a bound Group instance is present
        if ($group instanceof Group) {
            $user = $request->user();
            if (!$user) {
                throw (new ModelNotFoundException())->setModel(Group::class);
            }
            $isMember = $group->owner_id === $user->id
                || $group->invitations()
                    ->whereNotNull('accepted_at')
                    ->where('invited_user_id', $user->id)
                    ->exists();
            if (!$isMember) {
                // Convert to 404 (avoid existence leak)
                throw (new ModelNotFoundException())->setModel(Group::class, [$group->getKey()]);
            }
        }

        return $next($request);
    }
}
