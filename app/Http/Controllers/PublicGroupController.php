<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicGroupController extends Controller
{
    public function show(Request $request, string $code): Response
    {
        $group = Group::where('public_code', $code)->firstOrFail();

        $acceptedCount = $group->invitations()
            ->whereNotNull('accepted_at')
            ->whereNotNull('invited_user_id')
            ->count();
        $participantCount = 1 + $acceptedCount;
        $hasDraw = $group->assignments()->exists();
        $drawDate = $group->draw_at ? \Carbon\Carbon::parse($group->draw_at) : null;
        $today = now()->startOfDay();
        $daysUntilDraw = $drawDate ? $today->diffInDays($drawDate, false) : null;

        return Inertia::render('Groups/PublicShow', [
            'group' => [
                'name' => $group->name,
                'description' => $group->description,
                'participant_count' => $participantCount,
                'has_draw' => $hasDraw,
                'draw_date' => $drawDate?->toDateString(),
                'days_until_draw' => $daysUntilDraw,
                'currency' => $group->currency,
                'min_gift_cents' => $group->min_gift_cents,
                'max_gift_cents' => $group->max_gift_cents,
            ],
            'code' => $code,
        ]);
    }
}
