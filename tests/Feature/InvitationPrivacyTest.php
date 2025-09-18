<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected InvitationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(InvitationService::class);
    }

    private function issue(): array
    {
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);
        $email = 'target@example.test';
        $invitation = $this->service->create($group, $owner, $email);
        $plain = $invitation->getAttribute('plain_token');
        return [$owner, $group, $invitation, $plain, $email];
    }

    public function test_guest_cannot_see_invited_email(): void
    {
        [$owner, $group, $invitation, $plain, $email] = $this->issue();
        $this->get(route('invites.show', $plain))
            ->assertStatus(200)
            ->assertDontSee($email)
            ->assertSee('login'); // Public page prompt
    }

    public function test_authenticated_matching_user_can_accept(): void
    {
        [$owner, $group, $invitation, $plain, $email] = $this->issue();
        $user = User::factory()->create(['email' => $email]);
        $this->actingAs($user)
            ->get(route('invites.show', $plain))
            ->assertStatus(200)
            ->assertSee('"can_accept"') // key exists inside viewer
            ->assertSee('"viewer"')
            ->assertSee('"can_request_join":false');
    }

    public function test_authenticated_mismatched_user_cannot_accept_and_does_not_see_email(): void
    {
        [$owner, $group, $invitation, $plain, $email] = $this->issue();
        $user = User::factory()->create(['email' => 'other@example.test']);
        $response = $this->actingAs($user)->get(route('invites.show', $plain));
        $response->assertStatus(200)
            ->assertDontSee($email)
            ->assertSee('"viewer"')
            ->assertSee('"can_accept":false');
    }
}
