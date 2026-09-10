<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Switching the interface language.
 *
 * The choice is remembered in the session rather than on the user, because it is a
 * preference about this browser rather than a fact about the person: the same clerk
 * may want Arabic at the desk and English on a shared terminal.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('locales.supported')), 404);

        $request->session()->put(SetLocale::SESSION_KEY, $locale);

        return redirect()->back();
    }
}
