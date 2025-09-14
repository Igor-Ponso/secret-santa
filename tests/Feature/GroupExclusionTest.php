<?php

use App\Models\User;
use App\Models\Group;
use App\Models\GroupExclusion;
use App\Models\GroupInvitation;
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

function acceptInvitation(Group $group, User $invited, User $owner): void
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

it('respects a simple exclusion during draw', function () {
    $owner = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    acceptInvitation($group, $a, $owner);
    acceptInvitation($group, $b, $owner);

    // Exclude owner -> a (owner cannot gift a)
    GroupExclusion::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'excluded_user_id' => $a->id,
    ]);

    actingAs($owner);
    post(route('groups.draw.run', $group->id));

    $ownerAssignment = $group->assignments()->where('giver_user_id', $owner->id)->first();
    expect($ownerAssignment->receiver_user_id)->not->toBe($a->id);
});

it('may fail draw if constraints impossible (2 users mutual exclusion)', function () {
    $owner = User::factory()->create();
    $a = User::factory()->create();
    // Only two participants: owner & a
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    acceptInvitation($group, $a, $owner);

    GroupExclusion::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'excluded_user_id' => $a->id,
    ]);
    GroupExclusion::create([
        'group_id' => $group->id,
        'user_id' => $a->id,
        'excluded_user_id' => $owner->id,
    ]);

    actingAs($owner);
    post(route('groups.draw.run', $group->id));

    // With impossible constraints there should be no assignments persisted
    expect($group->assignments()->count())->toBe(0);
});
