<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InvitationStatusTest extends TestCase
{
    use RefreshDatabase;

    protected InvitationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(InvitationService::class);
    }

    private function issueInvitation(?Carbon $expiresAt = null): array
    {
        $groupOwner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $groupOwner->id]);
        $inviteeEmail = 'invitee@example.test';
        $invitation = $this->service->create($group, $groupOwner, $inviteeEmail);
        $plain = $invitation->getAttribute('plain_token');
        if ($expiresAt) {
            if ($invitation->getAttribute('plain_token')) {
                $invitation->offsetUnset('plain_token');
            }
            $invitation->forceFill(['expires_at' => $expiresAt])->save();
        }
        return [$groupOwner, $group, $invitation, $plain];
    }

    public function test_guest_can_view_pending_invitation(): void
    {
        [$owner, $group, $invitation, $plain] = $this->issueInvitation();
        $this->get(route('invites.show', $plain))
            ->assertStatus(200)
            ->assertSee('pending');
    }

    public function test_guest_sees_expired_status(): void
    {
        [$owner, $group, $invitation, $plain] = $this->issueInvitation(now()->subDay());
        $this->get(route('invites.show', $plain))
            ->assertStatus(200)
            ->assertSee('expired');
    }

    public function test_guest_sees_revoked_status(): void
    {
        [$owner, $group, $invitation, $plain] = $this->issueInvitation();
        if ($invitation->getAttribute('plain_token')) {
            $invitation->offsetUnset('plain_token');
        }
        $invitation->forceFill(['revoked_at' => now()])->save();
        $this->get(route('invites.show', $plain))
            ->assertStatus(200)
            ->assertSee('revoked');
    }

    public function test_guest_sees_accepted_status(): void
    {
        [$owner, $group, $invitation, $plain] = $this->issueInvitation();
        $invitedUser = User::factory()->create(['email' => $invitation->email]);
        if ($invitation->getAttribute('plain_token')) {
            $invitation->offsetUnset('plain_token');
        }
        $invitation->forceFill(['accepted_at' => now(), 'invited_user_id' => $invitedUser->id])->save();
        $this->get(route('invites.show', $plain))
            ->assertStatus(200)
            ->assertSee('accepted');
    }

    public function test_guest_sees_declined_status(): void
    {
        [$owner, $group, $invitation, $plain] = $this->issueInvitation();
        if ($invitation->getAttribute('plain_token')) {
            $invitation->offsetUnset('plain_token');
        }
        $invitation->forceFill(['declined_at' => now()])->save();
        $this->get(route('invites.show', $plain))
            ->assertStatus(200)
            ->assertSee('declined');
    }

    public function test_guest_sees_invalid_for_unknown_token(): void
    {
        $this->get(route('invites.show', 'nonexistenttoken'))
            ->assertStatus(200)
            ->assertSee('invalid');
    }

    public function test_guest_cannot_post_accept_or_decline(): void
    {
        [$owner, $group, $invitation, $plain] = $this->issueInvitation();
        $this->post(route('invites.accept', $plain))->assertRedirect('/login');
        $this->post(route('invites.decline', $plain))->assertRedirect('/login');
    }

    public function test_authenticated_user_with_matching_email_can_accept(): void
    {
        [$owner, $group, $invitation, $plain] = $this->issueInvitation();
        $user = User::factory()->create(['email' => $invitation->email]);
        $this->actingAs($user);
        $this->post(route('invites.accept', $plain))
            ->assertRedirect(route('groups.index'));
        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
    }

    public function test_authenticated_user_with_different_email_cannot_accept(): void
    {
        [$owner, $group, $invitation, $plain] = $this->issueInvitation();
        $user = User::factory()->create(['email' => 'other@example.test']);
        $this->actingAs($user);
        $this->post(route('invites.accept', $plain))
            ->assertStatus(403);
    }
}
