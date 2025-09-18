<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Group;
use App\Services\ShareLinkService;
use App\Models\GroupJoinRequest;
// Using fully-qualified Pest Laravel helper function calls inside test.

uses(RefreshDatabase::class);

test('user registering via share link creates pending join request with attribution', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $service = app(ShareLinkService::class);

    // Owner gera share link
    \Pest\Laravel\actingAs($owner);
    $res = $service->getOrCreate($group, $owner);
    $plain = $res['plain'];

    // Guest visita link => session deve armazenar pending_share_token
    auth()->logout();
    \Pest\Laravel\get(route('invites.show', $plain))->assertStatus(200);
    expect(session('pending_share_token'))->toBe($plain);

    // Registrar novo usuário
    \Pest\Laravel\post('/register', [
        'name' => 'Novo User',
        'email' => 'novo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    \Pest\Laravel\assertAuthenticated();

    // Join request criado
    $jr = GroupJoinRequest::where('group_id', $group->id)->where('user_id', auth()->id())->first();
    expect($jr)->not->toBeNull();
    expect($jr->status)->toBe('pending');
    expect($jr->share_link_id)->not->toBeNull();
});

