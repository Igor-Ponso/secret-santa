<?php

use App\Models\Group;
use App\Models\GroupExclusion;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\delete;

function acceptInviteForExclusions(Group $group, User $invited, User $owner): void
{
    GroupInvitation::create([
        'group_id' => $group->id,
        'inviter_id' => $owner->id,
        'email' => $invited->email,
        'token' => hash('sha256', Str::random(48)),
        'accepted_at' => now(),
        'invited_user_id' => $invited->id,
    ]);
}

it('owner can create and delete an exclusion', function () {
    $owner = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    acceptInviteForExclusions($group, $a, $owner);
    acceptInviteForExclusions($group, $b, $owner);

    actingAs($owner);
    post(route('groups.exclusions.store', $group->id), [
        'user_id' => $a->id,
        'excluded_user_id' => $b->id,
    ])->assertRedirect();

    expect(GroupExclusion::where('group_id', $group->id)->count())->toBe(1);

    $ex = GroupExclusion::first();
    delete(route('groups.exclusions.destroy', [$group->id, $ex->id]))->assertRedirect();
    expect(GroupExclusion::where('group_id', $group->id)->count())->toBe(0);
});

it('non-owner cannot create exclusion', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    acceptInviteForExclusions($group, $a, $owner);
    acceptInviteForExclusions($group, $b, $owner);

    actingAs($other);
    post(route('groups.exclusions.store', $group->id), [
        'user_id' => $a->id,
        'excluded_user_id' => $b->id,
    ])->assertForbidden();
});

it('cannot create duplicate exclusion pair', function () {
    $owner = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    acceptInviteForExclusions($group, $a, $owner);
    acceptInviteForExclusions($group, $b, $owner);

    actingAs($owner);
    post(route('groups.exclusions.store', $group->id), [
        'user_id' => $a->id,
        'excluded_user_id' => $b->id,
    ])->assertRedirect();

    post(route('groups.exclusions.store', $group->id), [
        'user_id' => $a->id,
        'excluded_user_id' => $b->id,
    ])->assertRedirect(); // idempotent, still single row

    expect(GroupExclusion::where('group_id', $group->id)->count())->toBe(1);
});
