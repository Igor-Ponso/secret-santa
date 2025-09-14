<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvitationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_new_invitation_link_when_none_exists(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('groups.invitations.link', $group));
        $response->assertOk();
        $link = $response->json('link');
        $this->assertIsString($link);
        $this->assertStringContainsString('/invites/', $link);
    }

    public function test_reuses_or_regenerates_invitation_link_on_subsequent_calls(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $user->id]);

        $first = $this->actingAs($user)->get(route('groups.invitations.link', $group));
        $first->assertOk();
        $firstLink = $first->json('link');

        $second = $this->get(route('groups.invitations.link', $group));
        $second->assertOk();
        $secondLink = $second->json('link');

        $this->assertIsString($secondLink);
        $this->assertStringContainsString('/invites/', $secondLink);
    }
}
