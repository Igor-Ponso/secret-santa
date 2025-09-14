<?php

use App\Models\User;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\Wishlist;
use App\Services\InvitationService;
use Illuminate\Support\Carbon;

it('redirects to onboarding after accepting when no wishlist items', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);
    $other = User::factory()->create();
    /** @var InvitationService $svc */
    $svc = app(InvitationService::class);
    $inv = $svc->create($group, $user, $other->email);
    $plain = $inv->getAttribute('plain_token');

    $this->actingAs($other);
    $this->post('/invites/' . $plain . '/accept')
        ->assertRedirect(route('groups.onboarding.show', $group->id));
});

it('redirects to wishlist when already has items', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);
    $other = User::factory()->create();
    /** @var InvitationService $svc */
    $svc = app(InvitationService::class);
    $inv = $svc->create($group, $user, $other->email);
    $plain = $inv->getAttribute('plain_token');

    // Pre-create wishlist item (simulate previous add via other flow)
    Wishlist::create([
        'user_id' => $other->id,
        'group_id' => $group->id,
        'item' => 'Book',
    ]);

    $this->actingAs($other);
    $this->post('/invites/' . $plain . '/accept')
        ->assertRedirect(route('groups.wishlist.index', $group->id));
});

it('batch onboarding creates items and redirects', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);
    $other = User::factory()->create();
    /** @var InvitationService $svc */
    $svc = app(InvitationService::class);
    $inv = $svc->create($group, $user, $other->email);
    $plain = $inv->getAttribute('plain_token');

    $this->actingAs($other);
    $this->post('/invites/' . $plain . '/accept');

    $resp = $this->post(route('groups.onboarding.store', $group->id), [
        'items' => [
            ['item' => 'Game'],
            ['item' => 'Headphones', 'note' => 'Noise cancelling'],
        ],
    ]);

    $resp->assertRedirect(route('groups.wishlist.index', $group->id));
    expect(Wishlist::where('group_id', $group->id)->where('user_id', $other->id)->count())->toBe(2);
});

it('skipping onboarding redirects to wishlist without items', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);
    $other = User::factory()->create();
    /** @var InvitationService $svc */
    $svc = app(InvitationService::class);
    $inv = $svc->create($group, $user, $other->email);
    $plain = $inv->getAttribute('plain_token');

    $this->actingAs($other);
    $this->post('/invites/' . $plain . '/accept');
    $this->post(route('groups.onboarding.skip', $group->id))
        ->assertRedirect(route('groups.wishlist.index', $group->id));
});
