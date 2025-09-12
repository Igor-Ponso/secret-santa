<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Priority: explicit cookie -> app default
        $cookieLocale = $request->cookie('locale');
        $raw = $cookieLocale ?? config('app.locale');

        // Map short codes to full variants if we standardize file names later
        $effective = match ($raw) {
            'pt' => 'pt_BR', // treat generic pt as Brazilian Portuguese variant
            default => $raw,
        };

        app()->setLocale($effective);
        return $next($request);
    }
}
