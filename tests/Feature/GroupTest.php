<?php

use App\Models\User;
use App\Models\Group;

it('requires auth to access groups index', function () {
    $this->get('/groups')->assertRedirect('/login');
});

it('lists only the authenticated user groups', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $userGroups = Group::factory()->count(2)->create(['owner_id' => $user->id]);
    $otherGroups = Group::factory()->count(3)->create(['owner_id' => $other->id]);

    $this->actingAs($user);
    $response = $this->get('/groups')->assertOk();

    $content = $response->getContent();
    // Count occurrences of opening <li for groups
    $liCount = substr_count($content, '<li');
    expect($liCount)->toBeGreaterThanOrEqual($userGroups->count());
    foreach ($otherGroups as $g) {
        $this->assertStringNotContainsString($g->name, $content);
    }
});

it('creates group with valid data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $payload = [
        'name' => 'Team Secret 2025',
        'description' => 'End of year exchange',
        'min_value' => 20,
        'max_value' => 100,
        'draw_at' => now()->addWeek()->toDateTimeString(),
    ];

    $this->post('/groups', $payload)->assertRedirect('/groups');

    expect(Group::where('name', 'Team Secret 2025')->exists())->toBeTrue();
});

it('validates group data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $payload = ['name' => '', 'max_value' => 10, 'min_value' => 20];

    $this->post('/groups', $payload)
        ->assertSessionHasErrors(['name', 'max_value']);
});
