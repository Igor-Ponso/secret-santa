<?php

use App\Models\Group;
use App\Models\User;
use App\Models\Assignment;
use App\Models\GroupInvitation;
use App\Services\DrawService;

it('stores receiver encrypted (cipher present, legacy plain optional)', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    // add 2 more participants
    $p1 = User::factory()->create();
    $p2 = User::factory()->create();
    foreach ([$p1, $p2] as $u) {
        GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'email' => $u->email,
            'invited_user_id' => $u->id,
            'accepted_at' => now(),
        ]);
    }

    app(DrawService::class)->run($group->fresh());

    $rows = Assignment::where('group_id', $group->id)->get();
    expect($rows)->not()->toBeEmpty();
    foreach ($rows as $row) {
        expect($row->receiver_cipher)->not()->toBeNull();
        // Accessor should give an int
        expect($row->decrypted_receiver_id)->toBeInt();
    }
});
