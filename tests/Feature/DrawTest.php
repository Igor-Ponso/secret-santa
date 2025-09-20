<?php

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Models\Assignment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use function Pest\Laravel\{actingAs, post, get};

it('runs a draw and creates valid assignments', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $u3 = User::factory()->create();

    // Create accepted invitations
    foreach ([$u1, $u2, $u3] as $u) {
        GroupInvitation::create([
            'group_id' => $group->id,
            'inviter_id' => $owner->id,
            'email' => $u->email,
            'token' => hash('sha256', Str::random(40)),
            'invited_user_id' => $u->id,
            'accepted_at' => now(),
        ]);
    }

    actingAs($owner);
    post(route('groups.draw.run', $group))->assertRedirect();

    $assignments = Assignment::where('group_id', $group->id)->get();
    expect($assignments)->toHaveCount(4); // owner + 3

    $givers = $assignments->pluck('giver_user_id');
    // Legacy plain column now null; use decrypted accessor
    $receivers = $assignments->map(fn($a) => $a->decrypted_receiver_id);

    // No self matches
    foreach ($assignments as $a) {
        expect($a->giver_user_id)->not->toBe($a->decrypted_receiver_id);
        expect($a->receiver_user_id)->toBeNull(); // legacy column cleared
    }

    // All unique givers & receivers
    expect($givers->unique())->toHaveCount(4);
    expect($receivers->unique())->toHaveCount(4);
});

it('prevents running draw twice', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $u1 = User::factory()->create();
    GroupInvitation::create([
        'group_id' => $group->id,
        'inviter_id' => $owner->id,
        'email' => $u1->email,
        'token' => hash('sha256', Str::random(40)),
        'invited_user_id' => $u1->id,
        'accepted_at' => now(),
    ]);

    actingAs($owner);
    post(route('groups.draw.run', $group));
    post(route('groups.draw.run', $group))->assertSessionHas('error');
});

it('allows participant to fetch their recipient', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();

    foreach ([$u1, $u2] as $u) {
        GroupInvitation::create([
            'group_id' => $group->id,
            'inviter_id' => $owner->id,
            'email' => $u->email,
            'token' => hash('sha256', Str::random(40)),
            'invited_user_id' => $u->id,
            'accepted_at' => now(),
        ]);
    }

    actingAs($owner);
    post(route('groups.draw.run', $group));

    actingAs($u1);
    $resp = get(route('groups.draw.recipient', $group));
    $resp->assertOk();
    $data = $resp->json('data.user');
    expect($data['id'])->not->toBe($u1->id);
});

it('forbids non participant from recipient', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $outsider = User::factory()->create();
    actingAs($outsider);
    // Outsider should get 404 due to EnsureGroupMembership masking
    get(route('groups.draw.recipient', $group))->assertNotFound();
});

it('exposes participant count, can_draw and recipient wishlist after draw', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();

    foreach ([$u1, $u2] as $u) {
        GroupInvitation::create([
            'group_id' => $group->id,
            'inviter_id' => $owner->id,
            'email' => $u->email,
            'token' => hash('sha256', Str::random(40)),
            'invited_user_id' => $u->id,
            'accepted_at' => now(),
        ]);
    }

    // Add wishlist items for one user
    \App\Models\Wishlist::create([
        'group_id' => $group->id,
        'user_id' => $u1->id,
        'item' => 'Copo Térmico',
        'note' => 'Qualquer cor escura',
        'url' => 'https://example.test/copo'
    ]);

    actingAs($owner);
    // Show before draw
    $show = get(route('groups.show', $group));
    $show->assertOk();
    $page = $show->viewData('page')['props']['group'];
    expect($page['participant_count'])->toBe(3);
    expect($page['can_draw'])->toBeTrue();

    post(route('groups.draw.run', $group));
    $showAfter = get(route('groups.show', $group));
    $pageAfter = $showAfter->viewData('page')['props']['group'];
    expect($pageAfter['has_draw'])->toBeTrue();
    expect($pageAfter['can_draw'])->toBeFalse();

    actingAs($u2);
    $recipientResp = get(route('groups.draw.recipient', $group))->assertOk();
    $json = $recipientResp->json('data');
    // Structure assertions
    expect($json)->toHaveKeys(['user', 'wishlist']);
    expect($json['wishlist'])->toBeArray();
});
