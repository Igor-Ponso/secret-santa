<?php

use App\Models\Group;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\put;

it('rejects updating draw_at to a past date', function () {
    $user = User::factory()->create();
    actingAs($user);
    $group = Group::factory()->create([
        'owner_id' => $user->id,
        'draw_at' => now()->addDays(3)->toDateString(),
    ]);

    $resp = put(route('groups.update', $group), [
        'name' => $group->name,
        'description' => $group->description,
        'draw_at' => now()->subDay()->toDateString(),
        'min_gift_cents' => $group->min_gift_cents,
        'max_gift_cents' => $group->max_gift_cents,
        'currency' => $group->currency ?? 'USD',
    ]);
    $resp->assertSessionHasErrors('draw_at');
});

it('allows updating draw_at to today', function () {
    $user = User::factory()->create();
    actingAs($user);
    $group = Group::factory()->create([
        'owner_id' => $user->id,
        'draw_at' => now()->addDays(2)->toDateString(),
    ]);

    $resp = put(route('groups.update', $group), [
        'name' => $group->name,
        'description' => $group->description,
        'draw_at' => now()->toDateString(),
        'min_gift_cents' => $group->min_gift_cents,
        'max_gift_cents' => $group->max_gift_cents,
        'currency' => $group->currency ?? 'USD',
    ]);
    $resp->assertSessionDoesntHaveErrors('draw_at');
});

it('allows updating draw_at to a future date', function () {
    $user = User::factory()->create();
    actingAs($user);
    $group = Group::factory()->create([
        'owner_id' => $user->id,
        'draw_at' => now()->addDays(1)->toDateString(),
    ]);

    $resp = put(route('groups.update', $group), [
        'name' => $group->name,
        'description' => $group->description,
        'draw_at' => now()->addDays(5)->toDateString(),
        'min_gift_cents' => $group->min_gift_cents,
        'max_gift_cents' => $group->max_gift_cents,
        'currency' => $group->currency ?? 'USD',
    ]);
    $resp->assertSessionDoesntHaveErrors('draw_at');
});
