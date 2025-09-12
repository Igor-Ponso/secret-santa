<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\WishlistController;
use App\Models\Group;
use App\Models\GroupInvitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

Route::middleware(['auth', 'verified'])->prefix('groups/{group}/wishlist')->name('groups.wishlist.')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('index');
    Route::post('/', [WishlistController::class, 'store'])->name('store');
    Route::put('/{wishlist}', [WishlistController::class, 'update'])->name('update');
    Route::delete('/{wishlist}', [WishlistController::class, 'destroy'])->name('destroy');
});

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    $user = Auth::user();

    $groups = Group::where('owner_id', $user->id)
        ->select(['id', 'name', 'draw_at'])
        ->get();

    $pendingInvitations = GroupInvitation::where('email', $user->email)
        ->whereNull('accepted_at')
        ->whereNull('declined_at')
        ->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
        })
        ->with('group:id,name')
        ->get()
        ->map(function ($i) {
            // Provide the plain token reconstruction not possible; can't expose hashed token.
            // For actions we can use a signed temporary route instead OR reuse the public routes expecting plain token.
            // Since we only store hashed token, we cannot recover plain token => adjust accept/decline to allow authenticated by id.
            return [
                'group' => ['id' => $i->group->id, 'name' => $i->group->name],
                'email' => $i->email,
                'expires_at' => $i->expires_at?->toISOString(),
                'token' => null, // placeholder until alt action endpoints created
                'id' => $i->id,
            ];
        });

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
        Route::get('/{group}', [\App\Http\Controllers\GroupController::class, 'show'])->name('show');
        Route::put('/{group}', [\App\Http\Controllers\GroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [\App\Http\Controllers\GroupController::class, 'destroy'])->name('destroy');
        // Limite: 5 convites por minuto por usuário
        Route::post('/{group}/invitations', [\App\Http\Controllers\GroupInvitationController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('invitations.store');
        Route::post('/{group}/invitations/{invitation}/revoke', [\App\Http\Controllers\GroupInvitationController::class, 'revoke'])
            ->middleware('throttle:10,1')
            ->name('invitations.revoke');
        Route::post('/{group}/invitations/{invitation}/resend', [\App\Http\Controllers\GroupInvitationController::class, 'resend'])
            ->middleware('throttle:10,1')
            ->name('invitations.resend');
        // Draw (Secret Santa assignment)
        Route::post('/{group}/draw', [\App\Http\Controllers\DrawController::class, 'run'])->name('draw.run');
        Route::get('/{group}/recipient', [\App\Http\Controllers\DrawController::class, 'recipient'])->name('draw.recipient');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/invites/{token}', [\App\Http\Controllers\PublicInvitationController::class, 'show'])->name('invites.show');
    Route::post('/invites/{token}/accept', [\App\Http\Controllers\PublicInvitationController::class, 'accept'])->name('invites.accept');
    Route::post('/invites/{token}/decline', [\App\Http\Controllers\PublicInvitationController::class, 'decline'])->name('invites.decline');
    // Authenticated direct actions by id (no plain token exposure)
    Route::post('/invitations/{invitation}/accept', [\App\Http\Controllers\UserInvitationActionController::class, 'accept'])->name('invites.auth.accept');
    Route::post('/invitations/{invitation}/decline', [\App\Http\Controllers\UserInvitationActionController::class, 'decline'])->name('invites.auth.decline');
});
