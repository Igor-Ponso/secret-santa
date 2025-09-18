<?php

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows accept buttons for matching pending invitation email', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);
    $invitee = User::factory()->create();
    $inv = GroupInvitation::factory()->create([
        'group_id' => $group->id,
        'inviter_id' => $user->id,
        'email' => $invitee->email,
        'expires_at' => now()->addDays(7),
    ]);
    actingAs($invitee);
    $plainToken = $inv->getAttribute('plain_token') ?? null; // factory doesn't set; simulate by reassigning a token
    // Force find by plain -> create hashed token manually
    $plainToken = 'testtoken' . str()->random(5);
    $inv->token = hash('sha256', $plainToken);
    $inv->save();

    $resp = get(route('invites.show', $plainToken), ['Accept' => 'application/json']);
    $resp->assertOk();
    $data = $resp->json('props.invitation');
    expect($data)->not->toBeNull();
    expect($data['viewer']['can_accept'])->toBeTrue();
});

it('shows request join when authenticated user not participant and email mismatch', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $invite = GroupInvitation::factory()->create([
        'group_id' => $group->id,
        'inviter_id' => $owner->id,
        'email' => 'someoneelse@example.com',
        'expires_at' => now()->addDays(3),
    ]);
    $user = User::factory()->create(); // different user
    actingAs($user);
    $plainToken = 'othertoken' . str()->random(4);
    $invite->token = hash('sha256', $plainToken);
    $invite->save();

    $resp = get(route('invites.show', $plainToken), ['Accept' => 'application/json']);
    $resp->assertOk();
    $data = $resp->json('props.invitation');
    expect($data)->not->toBeNull();
    expect($data['viewer']['can_accept'])->toBeFalse();
    expect($data['viewer']['can_request_join'])->toBeTrue();
});

it('redirects to group show when viewer participates (owner)', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $invite = GroupInvitation::factory()->create([
        'group_id' => $group->id,
        'inviter_id' => $owner->id,
        'email' => $owner->email . '.share-link',
        'expires_at' => now()->addDays(3),
    ]);
    actingAs($owner);
    $plainToken = 'ownertoken' . str()->random(4);
    $invite->token = hash('sha256', $plainToken);
    $invite->save();

    $resp = get(route('invites.show', $plainToken));
    $resp->assertRedirect(route('groups.show', $group));
});

it('redirects to group show when viewer participates (accepted invitation)', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    // Accepted invitation for participant
    $accepted = GroupInvitation::factory()->create([
        'group_id' => $group->id,
        'inviter_id' => $owner->id,
        'email' => $participant->email,
        'accepted_at' => now(),
        'invited_user_id' => $participant->id,
        'expires_at' => now()->addDays(7),
    ]);
    $plainToken = 'acceptedtoken' . str()->random(4);
    $accepted->token = hash('sha256', $plainToken);
    $accepted->save();
    actingAs($participant);
    $resp = get(route('invites.show', $plainToken));
    $resp->assertRedirect(route('groups.show', $group));
});

it('shows join_requested true after existing pending join request', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $invite = GroupInvitation::factory()->create([
        'group_id' => $group->id,
        'inviter_id' => $owner->id,
        'email' => 'someoneelse@example.com',
        'expires_at' => now()->addDays(5),
    ]);
    $user = User::factory()->create();
    // Create pending join request
    \App\Models\GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => 'pending',
    ]);
    actingAs($user);
    $plainToken = 'jrtoken' . str()->random(4);
    $invite->token = hash('sha256', $plainToken);
    $invite->save();
    $resp = get(route('invites.show', $plainToken), ['Accept' => 'application/json']);
    $resp->assertOk();
    $data = $resp->json('props.invitation');
    expect($data['viewer']['can_request_join'])->toBeFalse();
    expect($data['viewer']['join_requested'])->toBeTrue();
});
