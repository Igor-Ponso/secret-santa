<?php

use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('does not create an owner-looking pending invitation when requesting share link', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    actingAs($owner);
    // create group
    post(route('groups.store'), [
        'name' => 'Test Group',
        'description' => 'Desc',
        'draw_at' => now()->addDay()->toDateString(),
        'min_gift_cents' => null,
        'max_gift_cents' => null,
    ]);
    $groupId = \App\Models\Group::latest('id')->value('id');

    // request share link (creates/rotates share link but not an invitation)
    $json = getJson(route('groups.invitations.link', $groupId))->assertOk();
    $data = $json->json();
    expect($data)->toHaveKey('link');

    // Show page should still have zero invitations
    $response = get(route('groups.show', $groupId));
    // Inertia response contains 'page' variable
    $inertiaData = $response->original->getData()['page']['props'] ?? [];
    $payload = $inertiaData['group'] ?? [];
    $emails = collect($payload['invitations'] ?? [])->pluck('email');
    expect($emails)->toHaveCount(0);
});
