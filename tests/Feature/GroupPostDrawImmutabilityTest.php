<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

it('forbids updating a group after draw', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $group = Group::factory()->create([
        'owner_id' => $owner->id,
        'draw_at' => now()->toDateString(),
    ]);

    // accepted invitation
    $group->invitations()->create([
        'email' => $participant->email,
        'invited_user_id' => $participant->id,
        'inviter_id' => $owner->id,
        'accepted_at' => now(),
        'token' => Str::random(32),
        'expires_at' => now()->addDay(),
    ]);

    actingAs($owner);
    // Run draw
    post(route('groups.draw.run', $group->id))->assertRedirect();
    expect($group->fresh()->has_draw)->toBeTrue();

    // Attempt update
    put(route('groups.update', $group->id), [
        'name' => 'New Name',
        'description' => 'New desc',
        'min_gift_cents' => 1000,
        'max_gift_cents' => 2000,
        'draw_at' => now()->addDay()->toDateString(),
    ])->assertForbidden();
});
