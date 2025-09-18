<?php

use App\Console\Commands\RunDueDrawsCommand;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Notifications\ParticipantDrawResultNotification;
use App\Services\DrawService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseCount;

it('runs draw for due group and notifies participants', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $group = Group::factory()->create([
        'owner_id' => $owner->id,
        'draw_at' => now()->subMinute(),
    ]);

    // Two invited participants accepted
    $u1 = User::factory()->create();
    GroupInvitation::factory()->state([
        'group_id' => $group->id,
        'email' => $u1->email,
        'invited_user_id' => $u1->id,
        'accepted_at' => now()->subMinutes(5),
    ])->create();
    $u2 = User::factory()->create();
    GroupInvitation::factory()->state([
        'group_id' => $group->id,
        'email' => $u2->email,
        'invited_user_id' => $u2->id,
        'accepted_at' => now()->subMinutes(5),
    ])->create();

    artisan('groups:run-due-draws')->assertExitCode(0);

    $group->refresh();
    expect($group->has_draw)->toBeTrue();

    // 3 participants -> 3 assignments
    assertDatabaseCount('assignments', 3);

    Notification::assertSentTo([$owner, $u1, $u2], ParticipantDrawResultNotification::class);
});

it('is idempotent on second run', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $group = Group::factory()->create([
        'owner_id' => $owner->id,
        'draw_at' => now()->subMinute(),
    ]);

    $u1 = User::factory()->create();
    GroupInvitation::factory()->state([
        'group_id' => $group->id,
        'email' => $u1->email,
        'invited_user_id' => $u1->id,
        'accepted_at' => now()->subMinutes(5),
    ])->create();

    artisan('groups:run-due-draws');
    $initialAssignments = DB::table('assignments')->where('group_id', $group->id)->count();

    artisan('groups:run-due-draws');
    $afterAssignments = DB::table('assignments')->where('group_id', $group->id)->count();

    expect($afterAssignments)->toBe($initialAssignments);
});

it('skips group with insufficient participants', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $group = Group::factory()->create([
        'owner_id' => $owner->id,
        'draw_at' => now()->subMinute(),
    ]);

    artisan('groups:run-due-draws');

    $group->refresh();
    expect($group->has_draw)->toBeFalse();
    assertDatabaseCount('assignments', 0);
});
