<?php

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post; 
use function Pest\Laravel\get; 

it('owner can create invitation', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->for($owner, 'owner')->create();

    actingAs($owner);
    $service = app(\App\Services\InvitationService::class);
    $service->create($group, $owner, 'guest@example.com');
    expect(GroupInvitation::where('group_id', $group->id)->where('email', 'guest@example.com')->exists())->toBeTrue();
});

it('non owner cannot create invitation', function () {
    [$owner, $other] = User::factory()->count(2)->create(['email_verified_at' => now()]);
    $group = Group::factory()->for($owner, 'owner')->create();
    actingAs($other);
    post(route('groups.invitations.store', $group), ['email' => 'x@test.com'])
        ->assertForbidden();
});

it('owner cannot duplicate pending invitation', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->for($owner, 'owner')->create();
    $service = app(InvitationService::class);
    $service->create($group, $owner, 'dup@example.com');

    actingAs($owner);
    $resp = post(route('groups.invitations.store', $group), ['email' => 'dup@example.com']);
    $resp->assertRedirect();
    // still only one
    expect(GroupInvitation::where('group_id', $group->id)->where('email', 'dup@example.com')->count())->toBe(1);
});

it('user can accept invitation', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $invitee = User::factory()->create(['email' => 'accept@test.com', 'email_verified_at' => now()]);
    $group = Group::factory()->for($owner, 'owner')->create();
    $service = app(InvitationService::class);
    $inv = $service->create($group, $owner, 'accept@test.com');
    $token = $inv->getAttribute('plain_token');

    actingAs($invitee);
    post(route('invites.accept', $token))->assertRedirect();
    $inv->refresh();
    expect($inv->accepted_at)->not()->toBeNull();
    expect($inv->invited_user_id)->toBe($invitee->id);
});

it('user can decline invitation', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $invitee = User::factory()->create(['email' => 'decline@test.com', 'email_verified_at' => now()]);
    $group = Group::factory()->for($owner, 'owner')->create();
    $service = app(InvitationService::class);
    $inv = $service->create($group, $owner, 'decline@test.com');
    $token = $inv->getAttribute('plain_token');

    actingAs($invitee);
    post(route('invites.decline', $token))->assertRedirect();
    $inv->refresh();
    expect($inv->declined_at)->not()->toBeNull();
});

it('invitation token not found returns 404 on show', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    actingAs($user);
    get(route('invites.show', 'nonexistenttoken'))->assertNotFound();
});

it('cannot accept twice', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $invitee = User::factory()->create(['email' => 'repeat@test.com', 'email_verified_at' => now()]);
    $group = Group::factory()->for($owner, 'owner')->create();
    $service = app(InvitationService::class);
    $inv = $service->create($group, $owner, 'repeat@test.com');
    $token = $inv->getAttribute('plain_token');
    actingAs($invitee);
    post(route('invites.accept', $token));
    $firstAcceptedAt = $inv->refresh()->accepted_at;
    post(route('invites.accept', $token));
    expect($inv->refresh()->accepted_at)->toEqual($firstAcceptedAt);
});
