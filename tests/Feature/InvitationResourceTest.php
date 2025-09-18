<?php

use App\Models\Group;
use App\Models\User;
use App\Services\InvitationService;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('serializes invitation with viewer flags via resource', function () {
    $service = app(InvitationService::class);
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $email = 'person@test.local';
    $invitation = $service->create($group, $owner, $email);
    $plain = $invitation->getAttribute('plain_token');

    $user = User::factory()->create(['email' => $email]);
    actingAs($user);
    $resp = get(route('invites.show', $plain), ['Accept' => 'application/json']);
    $resp->assertOk();
    $data = $resp->json('props.invitation');
    expect($data['viewer']['can_accept'])->toBeTrue();
    expect($data['viewer']['can_request_join'])->toBeFalse();
});
