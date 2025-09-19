<?php

use App\Models\User;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\Wishlist;
use function Pest\Laravel\{actingAs, get};

it('returns avatar field for recipient after draw', function () {
    $owner = User::factory()->create();
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();

    $group = Group::factory()->create(['owner_id' => $owner->id]);

    foreach ([$u1, $u2] as $u) {
        GroupInvitation::create([
            'group_id' => $group->id,
            'inviter_id' => $owner->id,
            'email' => $u->email,
            'token' => hash('sha256', Str::random(40)),
            'invited_user_id' => $u->id,
            'accepted_at' => now(),
        ]);
    }

    // Run draw via service directly to avoid repeating draw logic test
    app(\App\Services\DrawService::class)->run($group);

    actingAs($owner);
    $response = get(route('groups.draw.recipient', $group));
    $response->assertOk();
    $json = $response->json('data.user');
    expect($json)->toHaveKeys(['id', 'name', 'avatar']);
});
