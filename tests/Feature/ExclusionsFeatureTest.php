<?php

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupExclusion;
use App\Models\User;
use App\Services\DrawService;
use function Pest\Laravel\actingAs;

function setupGroupWithParticipants(int $n): Group
{
    $owner = User::factory()->create();
    /** @var Group $group */
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    for ($i = 0; $i < $n - 1; $i++) { // minus owner
        $p = User::factory()->create();
        GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'email' => $p->email,
            'invited_user_id' => $p->id,
            'accepted_at' => now(),
        ]);
    }
    return $group->refresh();
}

test('owner can create exclusion', function () {
    $group = setupGroupWithParticipants(4);
    $owner = $group->owner;
    $participants = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values();
    $userA = $participants[0];
    $userB = $participants[1];
    actingAs($owner)
        ->post(route('groups.exclusions.store', $group), [
            'user_id' => $userA,
            'excluded_user_id' => $userB,
        ])->assertRedirect();
    expect(GroupExclusion::where('group_id', $group->id)->where('user_id', $userA)->where('excluded_user_id', $userB)->exists())->toBeTrue();
});

test('duplicate or inverse exclusion is rejected', function () {
    $group = setupGroupWithParticipants(4);
    $owner = $group->owner;
    $ids = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values();
    $a = $ids[0];
    $b = $ids[1];
    GroupExclusion::create(['group_id' => $group->id, 'user_id' => $a, 'excluded_user_id' => $b]);
    actingAs($owner)
        ->post(route('groups.exclusions.store', $group), [
            'user_id' => $b,
            'excluded_user_id' => $a,
        ])->assertSessionHasErrors();
});

test('cannot modify exclusions after draw', function () {
    $group = setupGroupWithParticipants(4);
    // run draw (sets has_draw)
    app(DrawService::class)->run($group);
    $group->refresh();
    $owner = $group->owner;
    $ids = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values();
    actingAs($owner)
        ->post(route('groups.exclusions.store', $group), [
            'user_id' => $ids[0],
            'excluded_user_id' => $ids[1],
        ])->assertSessionHasErrors();
});

test('rejected when user not an accepted participant', function () {
    $group = setupGroupWithParticipants(3);
    $owner = $group->owner;
    $outsider = User::factory()->create();
    $ids = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values();
    actingAs($owner)
        ->post(route('groups.exclusions.store', $group), [
            'user_id' => $ids[0],
            'excluded_user_id' => $outsider->id,
        ])->assertSessionHasErrors();
});

test('preview feasible returns sample', function () {
    $group = setupGroupWithParticipants(5);
    $owner = $group->owner;
    actingAs($owner)
        ->get(route('groups.exclusions.preview', $group))
        ->assertOk()
        ->assertJson(['feasible' => true])
        ->assertJsonStructure(['sample']);
});

test('preview infeasible returns feasible=false', function () {
    $group = setupGroupWithParticipants(3); // owner +2
    $owner = $group->owner;
    $ids = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values()->all();
    sort($ids);
    $a = $ids[0];
    $others = array_filter($ids, fn($id) => $id !== $a);
    foreach ($others as $o) {
        GroupExclusion::create(['group_id' => $group->id, 'user_id' => $a, 'excluded_user_id' => $o]);
    }
    actingAs($owner)
        ->get(route('groups.exclusions.preview', $group))
        ->assertOk()
        ->assertJson(['feasible' => false]);
});

test('owner can delete exclusion', function () {
    $group = setupGroupWithParticipants(4);
    $owner = $group->owner;
    $ids = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values();
    $a = $ids[0];
    $b = $ids[1];
    $ex = GroupExclusion::create(['group_id' => $group->id, 'user_id' => $a, 'excluded_user_id' => $b]);
    actingAs($owner)
        ->delete(route('groups.exclusions.destroy', [$group, $ex]))
        ->assertRedirect();
    expect(GroupExclusion::find($ex->id))->toBeNull();
});

test('impossible exclusion combination returns toast error and rolls back', function () {
    // group with only 2 participants (owner + 1 accepted) => excluding one from the other makes draw impossible
    $group = setupGroupWithParticipants(2);
    $owner = $group->owner;
    $ids = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values();
    $a = $ids[0];
    $b = $ids[1] ?? null;
    actingAs($owner)
        ->post(route('groups.exclusions.store', $group), [
            'user_id' => $a,
            'excluded_user_id' => $b,
        ])->assertRedirect();
    // Should not persist exclusion
    expect(GroupExclusion::where('group_id', $group->id)->count())->toBe(0);
});
