<?php

use App\Models\Group;
use App\Models\User;
use App\Models\GroupInvitation;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('owner can view group show page', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    actingAs($owner);
    get(route('groups.show', $group))->assertOk();
});

it('accepted participant can view group show page', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    GroupInvitation::create([
        'group_id' => $group->id,
        'inviter_id' => $owner->id,
        'email' => $participant->email,
        'token' => hash('sha256', Str::random(48)),
        'accepted_at' => now(),
        'invited_user_id' => $participant->id,
    ]);

    actingAs($participant);
    get(route('groups.show', $group))->assertOk();
});
