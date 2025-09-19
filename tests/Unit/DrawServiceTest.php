<?php

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Services\DrawService;
use App\Services\ExclusionService;

use function Pest\Laravel\artisan;

beforeEach(function () {
    // Nothing special yet
});

function createGroupWithParticipants(int $count): Group
{
    $owner = User::factory()->create();
    /** @var Group $group */
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    for ($i = 0; $i < $count - 1; $i++) { // -1 because owner counts
        $user = User::factory()->create();
        GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'email' => $user->email,
            'invited_user_id' => $user->id,
            'accepted_at' => now(),
        ]);
    }
    return $group->refresh();
}

test('solve returns valid permutation without exclusions', function () {
    $group = createGroupWithParticipants(5); // owner + 4 accepted
    $service = app(DrawService::class);
    $participants = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values()->all();
    $exclusions = [];
    $solution = $service->solve($participants, $exclusions);
    expect($solution)->not()->toBeNull();
    // Each giver unique receiver and sizes match
    expect(count($solution))->toBe(count($participants));
    expect(array_unique(array_values($solution)))->toHaveCount(count($participants));
    foreach ($solution as $giver => $receiver) {
        expect($giver)->not->toBe($receiver);
    }
});

test('solve respects simple exclusion constraint', function () {
    $group = createGroupWithParticipants(4); // 4 total
    $service = app(DrawService::class);
    $participants = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values()->all();
    sort($participants);
    $a = $participants[0];
    $b = $participants[1];
    // Exclude a -> b only
    $exclusions = [$a => [$b]];
    $solution = $service->solve($participants, $exclusions);
    expect($solution)->not()->toBeNull();
    expect($solution[$a])->not()->toBe($b);
});

test('solve detects impossibility when participant excludes all others', function () {
    $group = createGroupWithParticipants(3); // 3 total
    $service = app(DrawService::class);
    $participants = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values()->all();
    sort($participants);
    $a = $participants[0];
    $others = array_filter($participants, fn($id) => $id !== $a);
    // a excludes everyone else -> impossible
    $exclusions = [$a => array_values($others)];
    $solution = $service->solve($participants, $exclusions);
    expect($solution)->toBeNull();
});

test('sample returns consistent mapping structure when feasible', function () {
    $group = createGroupWithParticipants(5);
    $draw = app(DrawService::class);
    $sample = $draw->sample($group);
    expect($sample)->not()->toBeNull();
    $participants = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values()->all();
    expect(array_keys($sample))->toEqualCanonicalizing($participants);
});

test('sample returns null when impossible', function () {
    $group = createGroupWithParticipants(3);
    $draw = app(DrawService::class);
    $participants = $group->participants()->pluck('users.id')->push($group->owner_id)->unique()->values()->all();
    sort($participants);
    $a = $participants[0];
    $others = array_filter($participants, fn($id) => $id !== $a);
    // Insert exclusions a->others directly
    foreach ($others as $o) {
        \App\Models\GroupExclusion::create([
            'group_id' => $group->id,
            'user_id' => $a,
            'excluded_user_id' => $o,
        ]);
    }
    $sample = $draw->sample($group->fresh());
    expect($sample)->toBeNull();
});
