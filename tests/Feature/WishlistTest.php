<?php

/** @var \Tests\TestCase $this */

// TestCase binding provided globally in Pest.php

use App\Models\User;
use App\Models\Group;
use App\Models\Wishlist;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\put;
use function Pest\Laravel\get;
use function Pest\Laravel\delete;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @var \Tests\TestCase $this */
it('allows user to create, update, delete wishlist items and reflects count in groups index', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);

    // Another user's item should not count for current user.
    Wishlist::create([
        'user_id' => $other->id,
        'group_id' => $group->id,
        'item' => 'Other Item',
        'note' => 'x',
    ]);

    actingAs($user);

    // Create
    post(route('groups.wishlist.store', ['group' => $group->id]), [
        'item' => 'My Item',
        'note' => 'Nice',
        'url' => 'https://example.com/product'
    ])->assertRedirect();

    $item = Wishlist::where('group_id', $group->id)->where('user_id', $user->id)->first();
    expect($item)->not->toBeNull();
    expect($item->url)->toBe('https://example.com/product');

    // Update
    put(route('groups.wishlist.update', ['group' => $group->id, 'wishlist' => $item->id]), [
        'item' => 'My Item Updated',
        'note' => 'Better',
        'url' => 'https://example.com/updated'
    ])->assertRedirect();

    $item->refresh();
    expect($item->item)->toBe('My Item Updated');
    expect($item->url)->toBe('https://example.com/updated');

    // Groups index should show wishlist_count = 1
    $resp = get(route('groups.index'));
    $resp->assertOk();
    $serialized = collect($resp->original->getData()['page']['props']['groups'] ?? []);
    expect($serialized->first()['wishlist_count'])->toBe(1);

    // Delete
    delete(route('groups.wishlist.destroy', ['group' => $group->id, 'wishlist' => $item->id]))->assertRedirect();
    expect(Wishlist::where('id', $item->id)->exists())->toBeFalse();

    // Index again count 0
    $resp2 = get(route('groups.index'));
    $serialized2 = collect($resp2->original->getData()['page']['props']['groups'] ?? []);
    expect($serialized2->first()['wishlist_count'])->toBe(0);
});

/** @var \Tests\TestCase $this */
it('prevents updating another users wishlist item', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $foreignItem = Wishlist::create([
        'user_id' => $owner->id,
        'group_id' => $group->id,
        'item' => 'Owner Item',
        'note' => null,
    ]);

    actingAs($user);
    put(route('groups.wishlist.update', ['group' => $group->id, 'wishlist' => $foreignItem->id]), [
        'item' => 'Hack',
        'note' => 'X',
        'url' => 'https://example.com'
    ])->assertForbidden();
});
