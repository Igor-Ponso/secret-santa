<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LanguageController extends Controller
{
    public function edit()
    {
        // Expose standardized locale codes (using pt_BR variant)
        return Inertia::render('settings/Language', [
            'available_locales' => ['en', 'pt_BR', 'fr'],
            'current_locale' => app()->getLocale(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'locale' => ['required', 'in:en,pt_BR,fr']
        ]);

        $normalized = $data['locale'];
        $cookie = cookie('locale', $normalized, 60 * 24 * 365);

        return back()->with('flash', ['success' => __('Language updated.')])->withCookie($cookie);
    }
}
