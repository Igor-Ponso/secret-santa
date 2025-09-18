<?php

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\GroupInvitationNotification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

it('cannot invite the group owner email', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    actingAs($owner);

    $response = post(route('groups.invitations.store', $group), [
        'email' => $owner->email,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('flash.error');
    expect(GroupInvitation::count())->toBe(0);
});

it('cannot invite email of existing accepted participant', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $participant = User::factory()->create();
    // Existing accepted invitation for participant (simulate they joined through invite)
    GroupInvitation::factory()
        ->for($group, 'group')
        ->state([
            'inviter_id' => $owner->id,
            'email' => $participant->email,
        ])
        ->accepted($participant)
        ->create();

    actingAs($owner);

    $response = post(route('groups.invitations.store', $group), [
        'email' => $participant->email,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('flash.error');
    expect(GroupInvitation::count())->toBe(1); // still only the accepted one
});

it('allows inviting a new email', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    actingAs($owner);

    $email = 'newperson@example.com';
    $response = post(route('groups.invitations.store', $group), [
        'email' => $email,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('flash.success');
    $created = GroupInvitation::where('email', $email)->first();
    expect($created)->not->toBeNull();
    Notification::assertSentTo($created, GroupInvitationNotification::class);
});
