<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chooses the language for the request.
 *
 * A choice the person made is remembered in their session and wins. Failing that
 * the browser is asked, so someone arriving with an Arabic browser gets Arabic
 * without having to find the switch. Failing that, the configured default.
 */
class SetLocale
{
    public const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $supported = array_keys(config('locales.supported'));

        $chosen = $request->session()->get(self::SESSION_KEY);

        if (is_string($chosen) && in_array($chosen, $supported, true)) {
            return $chosen;
        }

        $preferred = $request->getPreferredLanguage($supported);

        return is_string($preferred) && in_array($preferred, $supported, true)
            ? $preferred
            : (string) config('app.locale');
    }
}
