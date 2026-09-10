<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers the appearance settings Tabler's switcher offers.
 *
 * Tabler drives them from `?theme=dark` links and localStorage, entirely in the
 * browser. That works, but the first paint of every fresh page is the default
 * colour until the script runs, and the choice is lost on another device.
 *
 * So the parameters are noticed here and kept in the session, and the layout
 * renders them as attributes on `<html>`. Tabler's own script still runs and still
 * owns the behaviour; this only means the server already knows the answer.
 */
class RememberTablerPreferences
{
    public const SESSION_KEY = 'tabler-preferences';

    /**
     * The settings worth persisting, with the value Tabler treats as the default.
     * A setting at its default is not rendered, which is what Tabler's own script
     * does with it.
     *
     * @var array<string, string>
     */
    public const SETTINGS = [
        'theme' => 'auto',
        'theme-base' => 'gray',
        'theme-font' => 'sans-serif',
        'theme-primary' => 'blue',
        'theme-radius' => '1',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $remembered = (array) $request->session()->get(self::SESSION_KEY, []);

        foreach (array_keys(self::SETTINGS) as $setting) {
            $value = $request->query($setting);

            if (is_string($value) && $value !== '') {
                $remembered[$setting] = $value;
            }
        }

        $request->session()->put(self::SESSION_KEY, $remembered);

        return $next($request);
    }

    /**
     * The attributes the layout should put on `<html>`: whatever has been chosen and
     * is not already the default.
     *
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        $remembered = (array) session(self::SESSION_KEY, []);
        $attributes = [];

        foreach (self::SETTINGS as $setting => $default) {
            $value = $remembered[$setting] ?? null;

            if (is_string($value) && $value !== '' && $value !== $default) {
                $attributes['data-bs-'.$setting] = $value;
            }
        }

        return $attributes;
    }
}
