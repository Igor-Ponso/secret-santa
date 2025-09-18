<?php

use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

it('rejects past draw_at date', function () {
    $user = User::factory()->create();
    actingAs($user);
    $response = post(route('groups.store'), [
        'name' => 'Past Group',
        'description' => 'Test',
        'draw_at' => now()->subDay()->toDateString(),
        'min_gift_cents' => 0,
        'max_gift_cents' => 1000,
        'currency' => 'USD',
    ]);
    $response->assertSessionHasErrors('draw_at');
});

it('accepts today draw_at date', function () {
    $user = User::factory()->create();
    actingAs($user);
    $response = post(route('groups.store'), [
        'name' => 'Today Group',
        'description' => 'Test',
        'draw_at' => now()->toDateString(),
        'min_gift_cents' => 0,
        'max_gift_cents' => 1000,
        'currency' => 'USD',
    ]);
    $response->assertSessionDoesntHaveErrors('draw_at');
});

it('accepts future draw_at date', function () {
    $user = User::factory()->create();
    actingAs($user);
    $response = post(route('groups.store'), [
        'name' => 'Future Group',
        'description' => 'Test',
        'draw_at' => now()->addDays(5)->toDateString(),
        'min_gift_cents' => 0,
        'max_gift_cents' => 1000,
        'currency' => 'USD',
    ]);
    $response->assertSessionDoesntHaveErrors('draw_at');
});
