<?php

use App\Models\User;
use App\Models\Group;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('requires auth to access groups index', function () {
    get('/groups')->assertRedirect('/login');
});

it('lists only the authenticated user groups', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $userGroups = Group::factory()->count(2)->create(['owner_id' => $user->id]);
    $otherGroups = Group::factory()->count(3)->create(['owner_id' => $other->id]);

    actingAs($user);
    $response = get('/groups')->assertOk();

    $content = $response->getContent();
    // Count occurrences of opening <li for groups
    $liCount = substr_count($content, '<li');
    expect($liCount)->toBeGreaterThanOrEqual($userGroups->count());
    foreach ($otherGroups as $g) {
        $this->assertStringNotContainsString($g->name, $content);
    }
});

it('creates group with valid data (gift range optional)', function () {
    $user = User::factory()->create();
    actingAs($user);

    $payload = [
        'name' => 'Team Secret 2025',
        'description' => 'End of year exchange',
        'min_gift_cents' => 2000, // R$20,00
        'max_gift_cents' => 10000, // R$100,00
        'draw_at' => now()->addWeek()->toDateString(),
    ];

    post('/groups', $payload)->assertRedirect('/groups');

    $group = Group::where('name', 'Team Secret 2025')->first();
    expect($group)->not->toBeNull();
    expect($group->min_gift_cents)->toBe(2000);
    expect($group->max_gift_cents)->toBe(10000);
});

it('validates group data (name required and max >= min)', function () {
    $user = User::factory()->create();
    actingAs($user);

    $payload = [
        'name' => '',
        'min_gift_cents' => 5000,
        'max_gift_cents' => 4000, // invalid: max < min
    ];

    post('/groups', $payload)
        ->assertSessionHasErrors(['name', 'max_gift_cents']);
});

it('allows creating group without gift range', function () {
    $user = User::factory()->create();
    actingAs($user);

    $payload = [
        'name' => 'No Range Group',
        'draw_at' => now()->addDays(3)->toDateString(),
    ];

    post('/groups', $payload)->assertRedirect('/groups');
    $group = Group::where('name', 'No Range Group')->first();
    expect($group->min_gift_cents)->toBeNull();
    expect($group->max_gift_cents)->toBeNull();
});
