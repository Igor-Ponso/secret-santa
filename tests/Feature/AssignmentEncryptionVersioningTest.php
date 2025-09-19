<?php

use App\Models\User;
use App\Models\Group;
use App\Models\Assignment;
use Illuminate\Support\Str;
use function Pest\Laravel\{actingAs, post, artisan};

// Ensure version 1 for deterministic assertions (after app boots)
beforeEach(function () {
    config()->set('encryption.assignments_version', 1);
});

it('creates only versioned ciphers with legacy column null', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();

    foreach ([$u1, $u2] as $u) {
        \App\Models\GroupInvitation::create([
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
    expect($assignments)->toHaveCount(3); // owner + 2

    foreach ($assignments as $a) {
        expect($a->receiver_user_id)->toBeNull();
        expect($a->receiver_cipher)->not->toBeNull();
        expect($a->receiver_cipher)->toStartWith('v1:');
        expect($a->decrypted_receiver_id)->not->toBeNull();
    }
});

it('verification command reports versions and succeeds', function () {
    artisan('assignments:verify-ciphers')->assertExitCode(0);
});

it('recrypt command re-encrypts legacy cipher to versioned', function () {
    // Manually insert a legacy-style row (cipher without prefix)
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    foreach ([$u1, $u2] as $u) {
        \App\Models\GroupInvitation::create([
            'group_id' => $group->id,
            'inviter_id' => $owner->id,
            'email' => $u->email,
            'token' => hash('sha256', Str::random(40)),
            'invited_user_id' => $u->id,
            'accepted_at' => now(),
        ]);
    }

    // simulate legacy draw: create assignments with unprefixed cipher
    $ids = [$owner->id, $u1->id, $u2->id];
    $pairs = [$owner->id => $u1->id, $u1->id => $u2->id, $u2->id => $owner->id];
    foreach ($pairs as $g => $r) {
        $a = new Assignment([
            'group_id' => $group->id,
            'giver_user_id' => $g,
        ]);
        // direct encrypt without prefix
        $a->receiver_cipher = encrypt((string) $r);
        $a->save();
    }

    // Run recrypt forcing even same version (here legacy -> v1)
    artisan('assignments:recrypt')->assertExitCode(0);

    $updated = Assignment::where('group_id', $group->id)->get();
    foreach ($updated as $a) {
        expect($a->receiver_cipher)->toStartWith('v1:');
        expect($a->decrypted_receiver_id)->not->toBeNull();
    }
});
