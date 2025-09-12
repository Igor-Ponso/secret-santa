<?php

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows accepted participant group under participating list and hides invitations on show', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    // Accepted invitation linking participant to group
    GroupInvitation::create([
        'group_id' => $group->id,
        'inviter_id' => $owner->id,
        'email' => $participant->email,
        'token' => hash('sha256', Str::random(48)),
        'accepted_at' => now(),
        'invited_user_id' => $participant->id,
    ]);

    actingAs($participant);

    // Index should list group in participating not owned groups
    $index = get(route('groups.index'))->assertOk();
    $props = $index->viewData('page')['props'];
    expect($props['groups'])->toBeArray()->and($props['groups'])->toHaveCount(0); // no owned groups
    expect($props['participating'])->toBeArray();
    $ids = collect($props['participating'])->pluck('id');
    expect($ids)->toContain($group->id);

    // Show page
    $show = get(route('groups.show', $group))->assertOk();
    $groupPayload = $show->viewData('page')['props']['group'];
    expect($groupPayload['is_owner'])->toBeFalse();
    expect($groupPayload['participants'])->toBeArray();
    $participantIds = collect($groupPayload['participants'])->pluck('id');
    expect($participantIds)->toContain($owner->id)->toContain($participant->id);
    // Invitations only for owner, so key should be absent or empty
    expect(array_key_exists('invitations', $groupPayload) ? $groupPayload['invitations'] : [])->toBeArray()->toHaveCount(0);
});
