<?php

use App\Models\Group;
use App\Models\User;
use App\Services\DrawService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

it('owner can run manual draw when enough participants even before draw date', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $group = Group::factory()->create([
        'owner_id' => $owner->id,
        'draw_at' => now()->addDays(5)->toDateString(),
    ]);

    // Simulate accepted invitation for $other (direct attach via invitations factory pattern)
    $group->invitations()->create([
        'email' => $other->email,
        'invited_user_id' => $other->id,
        'inviter_id' => $owner->id,
        'accepted_at' => now(),
        'token' => Str::random(32),
        'expires_at' => now()->addDay(),
    ]);

    actingAs($owner);
    post(route('groups.draw.run', $group->id))->assertRedirect();

    expect($group->fresh()->has_draw)->toBeTrue();
});

it('non owner cannot run manual draw', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $group = Group::factory()->create([
        'owner_id' => $owner->id,
        'draw_at' => now()->toDateString(),
    ]);

    $group->invitations()->create([
        'email' => $other->email,
        'invited_user_id' => $other->id,
        'inviter_id' => $owner->id,
        'accepted_at' => now(),
        'token' => Str::random(32),
        'expires_at' => now()->addDay(),
    ]);

    actingAs($other);
    post(route('groups.draw.run', $group->id))->assertForbidden();
});

it('cannot run manual draw twice', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $group = Group::factory()->create([
        'owner_id' => $owner->id,
        'draw_at' => now()->toDateString(),
    ]);

    $group->invitations()->create([
        'email' => $other->email,
        'invited_user_id' => $other->id,
        'inviter_id' => $owner->id,
        'accepted_at' => now(),
        'token' => Str::random(32),
        'expires_at' => now()->addDay(),
    ]);

    actingAs($owner);
    post(route('groups.draw.run', $group->id))->assertRedirect();
    $firstAssignments = $group->assignments()->count();
    expect($firstAssignments)->toBeGreaterThan(0);

    post(route('groups.draw.run', $group->id))
        ->assertSessionHas('error');
});
