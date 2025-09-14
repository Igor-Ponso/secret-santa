<?php

use App\Models\Group;
use App\Models\User;
use App\Models\Wishlist;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

it('normalizes single item URL by adding https scheme when missing', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);
    actingAs($user);
    post(route('groups.wishlist.store', $group), [
        'item' => 'Gadget',
        'url' => 'example.com/gadget',
    ])->assertRedirect();

    $w = Wishlist::where('group_id', $group->id)->where('user_id', $user->id)->first();
    expect($w->url)->toStartWith('https://');
});

it('normalizes each batch item URL when missing scheme', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);
    actingAs($user);
    post(route('groups.wishlist.store.batch', $group->id), [
        'items' => [
            ['item' => 'Item 1', 'url' => 'shop.com/1'],
            ['item' => 'Item 2', 'url' => 'http://already.com/2'],
            ['item' => 'Item 3', 'url' => 'another.net/x'],
        ],
    ])->assertRedirect();

    $urls = Wishlist::where('group_id', $group->id)
        ->where('user_id', $user->id)
        ->orderBy('item')
        ->pluck('url')
        ->values()
        ->all();
    expect($urls)->toHaveCount(3);
    // Order by item ensures Item 1, Item 2, Item 3 sequence
    expect($urls[0])->toStartWith('https://'); // Item 1 normalized
    expect($urls[1])->toStartWith('http://');  // Item 2 already had scheme
    expect($urls[2])->toStartWith('https://'); // Item 3 normalized
});
