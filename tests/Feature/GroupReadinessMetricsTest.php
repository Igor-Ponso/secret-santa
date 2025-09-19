<?php

use App\Models\Group;
use App\Models\User;
use App\Models\GroupInvitation;
use App\Models\Wishlist;

it('computes readiness metrics (participants + wishlist coverage)', function () {
    $owner = User::factory()->create();
    \Pest\Laravel\actingAs($owner); // Pest helper
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $u1 = User::factory()->create();
    GroupInvitation::factory()->create([
        'group_id' => $group->id,
        'email' => $u1->email,
        'invited_user_id' => $u1->id,
        'accepted_at' => now(),
    ]);

    Wishlist::create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'item' => 'Livro',
    ]);

    $response = \Pest\Laravel\get(route('groups.show', $group));
    $response->assertStatus(200);
    // Inertia response page data
    $props = $response->viewData('page')['props'] ?? [];
    $metrics = $props['group']['metrics'] ?? [];

    expect($metrics)
        ->toHaveKeys(['pending', 'accepted', 'declined', 'revoked', 'min_participants_met', 'wishlist_coverage_percent', 'ready_for_draw']);
    expect($metrics['min_participants_met'])->toBeTrue();
    expect((int) $metrics['wishlist_coverage_percent'])->toBe(50);
    expect($metrics['ready_for_draw'])->toBeTrue();
});
