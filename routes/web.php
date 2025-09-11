<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

use App\Models\Group;
use App\Models\GroupInvitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

Route::get('dashboard', function () {
    $user = Auth::user();

    // Grupos do usuário
    $groups = Group::where('owner_id', $user->id)
        ->select(['id', 'name', 'draw_at'])
        ->get();

    // Convites recebidos (pendentes)
    $pendingInvitations = GroupInvitation::where('email', $user->email)
        ->whereNull('accepted_at')
        ->whereNull('declined_at')
        ->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
        })
        ->with('group:id,name')
        ->get()
        ->map(fn($i) => [
            'group' => [
                'id' => $i->group->id,
                'name' => $i->group->name,
            ],
            'email' => $i->email,
            'expires_at' => $i->expires_at?->toISOString(),
        ]);

    // Próximos sorteios (draw_at futuro)
    $upcomingDraws = $groups->filter(fn($g) => $g->draw_at && $g->draw_at > Carbon::now())
        ->sortBy('draw_at')
        ->map(fn($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'draw_at' => $g->draw_at?->toISOString(),
        ])->values();

    // Atividades recentes (mock)
    $recentActivities = [];

    return Inertia::render('Dashboard', [
        'groupsCount' => $groups->count(),
        'pendingInvitationsCount' => $pendingInvitations->count(),
        'upcomingDraws' => $upcomingDraws ?? [],
        'pendingInvitations' => $pendingInvitations ?? [],
        'recentActivities' => $recentActivities,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

Route::middleware(['auth'])
    ->prefix('groups')
    ->name('groups.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\GroupController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\GroupController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\GroupController::class, 'store'])->name('store');
        Route::get('/{group}/edit', [\App\Http\Controllers\GroupController::class, 'edit'])->name('edit');
        Route::put('/{group}', [\App\Http\Controllers\GroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [\App\Http\Controllers\GroupController::class, 'destroy'])->name('destroy');
        // Limite: 5 convites por minuto por usuário
        Route::post('/{group}/invitations', [\App\Http\Controllers\GroupInvitationController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('invitations.store');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/invites/{token}', [\App\Http\Controllers\PublicInvitationController::class, 'show'])->name('invites.show');
    Route::post('/invites/{token}/accept', [\App\Http\Controllers\PublicInvitationController::class, 'accept'])->name('invites.accept');
    Route::post('/invites/{token}/decline', [\App\Http\Controllers\PublicInvitationController::class, 'decline'])->name('invites.decline');
});
