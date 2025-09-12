<?php

use App\Models\Group;
use App\Models\User;
use App\Models\GroupJoinRequest;
use App\Services\GroupService;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\get;

it('generates join code on group creation via service', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $service = app(GroupService::class);
    $group = $service->create(['name' => 'My Test Group'], $owner);
    expect($group->join_code)->not()->toBeNull()->and(strlen($group->join_code))->toBe(12);
});

it('factory provides join code', function () {
    $group = Group::factory()->create();
    expect($group->join_code)->not()->toBeNull()->and(strlen($group->join_code))->toBe(12);
});

it('owner can regenerate join code', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->for($owner, 'owner')->create();
    $original = $group->join_code;
    actingAs($owner);
    post(route('groups.regenerate_code', $group))->assertRedirect();
    $group->refresh();
    expect($group->join_code)->not()->toBe($original)->and(strlen($group->join_code))->toBe(12);
});

it('non owner cannot regenerate join code', function () {
    [$owner, $other] = User::factory()->count(2)->create(['email_verified_at' => now()]);
    $group = Group::factory()->for($owner, 'owner')->create();
    $original = $group->join_code;
    actingAs($other);
    post(route('groups.regenerate_code', $group))->assertForbidden();
    $group->refresh();
    expect($group->join_code)->toBe($original);
});

it('old join code becomes invalid after regeneration', function () {
    [$owner, $user] = User::factory()->count(2)->create(['email_verified_at' => now()]);
    $group = Group::factory()->for($owner, 'owner')->create();
    $old = $group->join_code;
    actingAs($owner);
    post(route('groups.regenerate_code', $group));
    $group->refresh();
    expect($group->join_code)->not()->toBe($old);
    // user tries old code
    actingAs($user);
    $resp = post(route('groups.join_requests.join_by_code'), ['code' => $old]);
    $resp->assertSessionHas('flash');
    expect(session('flash')['error'] ?? null)->toBe('Código inválido.');
    // ensure no join request created with old code attempt
    expect(GroupJoinRequest::where('group_id', $group->id)->where('user_id', $user->id)->exists())->toBeFalse();
});

it('user can request join with current code', function () {
    [$owner, $user] = User::factory()->count(2)->create(['email_verified_at' => now()]);
    $group = Group::factory()->for($owner, 'owner')->create();
    actingAs($user);
    $resp = post(route('groups.join_requests.join_by_code'), ['code' => $group->join_code]);
    $resp->assertSessionHas('flash');
    expect(GroupJoinRequest::where('group_id', $group->id)->where('user_id', $user->id)->exists())->toBeTrue();
});
