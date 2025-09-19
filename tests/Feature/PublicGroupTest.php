<?php

use App\Models\Group;
use App\Models\User;
use function Pest\Laravel\{get, actingAs};

it('shows public group landing by code', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $resp = get('/g/' . $group->public_code);
    $resp->assertStatus(200);
    $resp->assertSee($group->name);
});

it('returns 404 for invalid public group code', function () {
    $resp = get('/g/doesNotExist123');
    $resp->assertStatus(404);
});

it('returns 404 when accessing group by id not a member', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    actingAs($stranger);
    $resp = get('/groups/' . $group->id);
    $resp->assertStatus(404);
});
