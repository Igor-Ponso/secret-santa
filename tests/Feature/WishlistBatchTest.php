<?php

use App\Models\User;
use App\Models\Group;
use App\Models\Wishlist;
// Using the bound TestCase ($this) like other feature tests for HTTP helpers.

it('creates multiple wishlist items via batch endpoint', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);

    $this->actingAs($user);

    $resp = $this->post(route('groups.wishlist.store.batch', $group->id), [
        'items' => [
            ['item' => 'Board Game'],
            ['item' => 'Coffee Mug', 'note' => 'Large'],
            ['item' => 'Novel', 'url' => 'http://example.com/book'],
        ],
    ]);

    // Debug: ensure we actually hit controller
    $resp->assertStatus(302);
    // If validation failed, there will be errors in session
    if (session('errors')) {
        // Provide quick visibility
        fwrite(STDERR, 'Validation errors: ' . json_encode(session('errors')->all()) . "\n");
    }
    // Ensure redirect came back to wishlist index (back())
    // $resp->assertRedirect(route('groups.wishlist.index', $group->id)); // optional if behavior changes

    $items = Wishlist::where('group_id', $group->id)->where('user_id', $user->id)->get();
    expect($items)->toHaveCount(3);
    $novel = $items->firstWhere('item', 'Novel');
    expect($novel->url)->toMatch('/^https?:\/\//');
});

it('rejects more than 5 items in batch', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);
    $this->actingAs($user);

    $payload = ['items' => []];
    for ($i = 0; $i < 6; $i++) {
        $payload['items'][] = ['item' => 'Item ' . $i];
    }

    $resp = $this->post(route('groups.wishlist.store.batch', $group->id), $payload);
    $resp->assertSessionHasErrors(['items']);
});

it('requires at least one valid item in batch', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);
    $this->actingAs($user);

    $resp = $this->post(route('groups.wishlist.store.batch', $group->id), ['items' => []]);
    $resp->assertSessionHasErrors(['items']);
});
