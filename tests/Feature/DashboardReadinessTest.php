<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use App\Models\GroupInvitation;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReadinessTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_displays_readiness_summary_for_owned_groups(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $group = Group::factory()->create(['owner_id' => $owner->id]);

        // Create an accepted invitation (participant #2)
        $participant = User::factory()->create();
        GroupInvitation::create([
            'group_id' => $group->id,
            'email' => $participant->email,
            'inviter_id' => $owner->id,
            'invited_user_id' => $participant->id,
            'accepted_at' => now(),
            'token' => hash('sha256', 'dummy'),
        ]);

        // Only one wishlist (50% coverage when threshold default 50 and 2 participants total)
        Wishlist::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'item' => 'Item 1',
        ]);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        // Inertia responses in tests may render HTML; attempt to extract JSON script tag
        $pageProps = null;
        if ($response->headers->get('content-type') === 'application/json') {
            $pageProps = $response->json('page.props.readiness');
        } else {
            // Fallback: parse the Inertia JSON embedded in the HTML (look for data-page attribute)
            $content = $response->getContent();
            if (preg_match('/data-page=\"([^\"]+)\"/', $content, $m)) {
                $decoded = html_entity_decode($m[1]);
                $json = json_decode($decoded, true);
                $pageProps = $json['props']['readiness'] ?? null;
            }
        }

        $this->assertNotNull($pageProps, 'Failed to extract readiness props from Inertia response');
        $page = $pageProps;

        $this->assertIsArray($page);
        $this->assertCount(1, $page);
        $entry = $page[0];
        $this->assertEquals($group->id, $entry['id']);
        $this->assertEquals(2, $entry['participants']);
        $this->assertEquals(50, $entry['coverage']);
        $this->assertArrayHasKey('ready', $entry);
    }
}
