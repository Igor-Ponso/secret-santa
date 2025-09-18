<?php

namespace Database\Factories;

use App\Models\GroupInvitation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/** @extends Factory<GroupInvitation> */
class GroupInvitationFactory extends Factory
{
    protected $model = GroupInvitation::class;

    public function definition(): array
    {
        $token = Str::random(48);
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);
        return [
            'group_id' => $group->id,
            'inviter_id' => $owner->id,
            'email' => fake()->unique()->safeEmail(),
            'token' => hash('sha256', $token),
            'expires_at' => Carbon::now()->addDays(14),
        ];
    }

    public function accepted(User $user = null): self
    {
        return $this->state(function (array $attrs) use ($user) {
            $participant = $user ?: User::factory()->create();
            return [
                'accepted_at' => Carbon::now(),
                'invited_user_id' => $participant->id,
            ];
        });
    }
}
