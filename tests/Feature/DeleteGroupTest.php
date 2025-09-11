<?php

use App\Models\Group;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;

it('deletes a group the user owns', function () {
    $user = User::factory()->create();
    actingAs($user);
    $group = Group::factory()->create(['owner_id' => $user->id]);

    $response = delete(route('groups.destroy', $group));
    $response->assertRedirect(route('groups.index'));
    expect(Group::withTrashed()->find($group->id))->not()->toBeNull();
    expect(Group::find($group->id))->toBeNull();
});

it("cannot delete someone else's group", function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $other->id]);
    actingAs($user);

    $response = delete(route('groups.destroy', $group));
    $response->assertForbidden();
    expect(Group::find($group->id))->not()->toBeNull();
});
