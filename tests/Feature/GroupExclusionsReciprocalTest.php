<?php

use App\Models\{Group, GroupExclusion, GroupInvitation, User};
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

function acceptInviteRec(Group $group, User $owner, User $user): void
{
    GroupInvitation::create([
        'group_id' => $group->id,
        'inviter_id' => $owner->id,
        'email' => $user->email,
        'token' => hash('sha256', Str::random(48)),
        'accepted_at' => now(),
        'invited_user_id' => $user->id,
    ]);
}

it('creates reciprocal exclusions when requested', function () {
    $owner = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    acceptInviteRec($group, $owner, $a);
    acceptInviteRec($group, $owner, $b);

    actingAs($owner);
    post(route('groups.exclusions.store', $group->id), [
        'user_id' => $a->id,
        'excluded_user_id' => $b->id,
        'reciprocal' => true,
    ])->assertRedirect();

    $rows = GroupExclusion::where('group_id', $group->id)->get();
    expect($rows->count())->toBe(2)
        ->and($rows->pluck('user_id')->sort()->values()->all())
        ->toBe([$a->id, $b->id]);
});

it('blocks exclusion that would make draw impossible', function () {
    $owner = User::factory()->create();
    $a = User::factory()->create();
    // Only two participants total
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    acceptInviteRec($group, $owner, $a);

    actingAs($owner);
    // A single directional exclusion in a 2-person group already makes a perfect draw impossible.
    // It should be rejected and rolled back.
    post(route('groups.exclusions.store', $group->id), [
        'user_id' => $owner->id,
        'excluded_user_id' => $a->id,
    ])->assertSessionHas('flash.error');

    expect(GroupExclusion::where('group_id', $group->id)->count())->toBe(0);
});
