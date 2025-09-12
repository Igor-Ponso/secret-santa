<?php

use App\Models\{User, Group, GroupInvitation, Assignment};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;

function acceptInvite(Group $group, User $owner, User $user): GroupInvitation
{
    return GroupInvitation::create([
        'group_id' => $group->id,
        'inviter_id' => $owner->id,
        'invited_user_id' => $user->id,
        'email' => $user->email,
        'token' => Hash::make(Str::random(40)),
        'accepted_at' => now(),
    ]);
}

// Database refreshing handled globally (e.g., RefreshDatabase in TestCase)

it('owner can remove a participant when more than 2 total', function () {
    $owner = User::factory()->create();
    $g = Group::factory()->create(['owner_id' => $owner->id]);
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    acceptInvite($g, $owner, $u1);
    acceptInvite($g, $owner, $u2);

    \Pest\Laravel\actingAs($owner)
        ->delete(route('groups.participants.remove', [$g, $u1]))
        ->assertRedirect();

    expect($g->refresh()->invitations()->whereNotNull('revoked_at')->count())->toBe(1);
});

it('cannot remove owner', function () {
    $owner = User::factory()->create();
    $g = Group::factory()->create(['owner_id' => $owner->id]);
    $response = \Pest\Laravel\actingAs($owner)
        ->delete(route('groups.participants.remove', [$g, $owner]));
    $response->assertSessionHas('flash.error');
});

it('cannot remove if would drop below 2 participants', function () {
    $owner = User::factory()->create();
    $g = Group::factory()->create(['owner_id' => $owner->id]);
    $u1 = User::factory()->create();
    acceptInvite($g, $owner, $u1);
    $response = \Pest\Laravel\actingAs($owner)
        ->delete(route('groups.participants.remove', [$g, $u1]));
    $response->assertSessionHas('flash.error');
    expect($g->refresh()->invitations()->whereNull('revoked_at')->whereNotNull('accepted_at')->count())->toBe(1);
});

it('cannot remove after draw executed', function () {
    $owner = User::factory()->create();
    $g = Group::factory()->create(['owner_id' => $owner->id]);
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    acceptInvite($g, $owner, $u1);
    acceptInvite($g, $owner, $u2);
    Assignment::create(['group_id' => $g->id, 'giver_user_id' => $owner->id, 'receiver_user_id' => $u1->id]);
    Assignment::create(['group_id' => $g->id, 'giver_user_id' => $u1->id, 'receiver_user_id' => $u2->id]);
    Assignment::create(['group_id' => $g->id, 'giver_user_id' => $u2->id, 'receiver_user_id' => $owner->id]);
    $response = \Pest\Laravel\actingAs($owner)
        ->delete(route('groups.participants.remove', [$g, $u1]));
    $response->assertSessionHas('flash.error');
    expect($g->refresh()->invitations()->whereNull('revoked_at')->whereNotNull('accepted_at')->count())->toBe(2);
});

it('owner can transfer ownership to accepted participant', function () {
    $owner = User::factory()->create();
    $g = Group::factory()->create(['owner_id' => $owner->id]);
    $u1 = User::factory()->create();
    acceptInvite($g, $owner, $u1);
    \Pest\Laravel\actingAs($owner)
        ->post(route('groups.transfer_ownership', $g), ['user_id' => $u1->id])
        ->assertRedirect();
    expect($g->refresh()->owner_id)->toBe($u1->id);
});

it('cannot transfer ownership to non participant', function () {
    $owner = User::factory()->create();
    $g = Group::factory()->create(['owner_id' => $owner->id]);
    $outsider = User::factory()->create();
    $response = \Pest\Laravel\actingAs($owner)
        ->post(route('groups.transfer_ownership', $g), ['user_id' => $outsider->id]);
    $response->assertSessionHas('flash.error');
    expect($g->refresh()->owner_id)->toBe($owner->id);
});
