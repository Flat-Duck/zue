<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * The response headers that stop a page being framed, sniffed or scripted from
 * elsewhere, and the content security policy.
 *
 * The nonce is minted once per request and handed to Vite, which puts it on the
 * bundle tags; Livewire reads the same one for its own inline config. The few
 * inline scripts left in the views carry `@cspNonce` themselves.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('security.force_https') && ! $request->isSecure() && ! app()->runningUnitTests()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $nonce = Str::random(32);

        Vite::useCspNonce($nonce);

        /** @var Response $response */
        $response = $next($request);

        foreach ((array) config('security.headers', []) as $header => $value) {
            $response->headers->set($header, $value);
        }

        if ($request->isSecure() && config('security.hsts.enabled', false)) {
            $response->headers->set('Strict-Transport-Security', $this->hsts());
        }

        if (config('security.csp.enabled', true)) {
            $response->headers->set(
                config('security.csp.enforce', true)
                    ? 'Content-Security-Policy'
                    : 'Content-Security-Policy-Report-Only',
                $this->policy($nonce)
            );
        }

        return $response;
    }

    private function hsts(): string
    {
        $value = 'max-age='.(int) config('security.hsts.max_age', 31536000);

        if (config('security.hsts.include_subdomains', true)) {
            $value .= '; includeSubDomains';
        }

        return $value;
    }

    private function policy(string $nonce): string
    {
        $parts = [];

        foreach ((array) config('security.csp.directives', []) as $directive => $sources) {
            $sources = array_map(
                fn (string $source): string => $source === "'nonce'" ? "'nonce-{$nonce}'" : $source,
                (array) $sources
            );

            $parts[] = $directive.' '.implode(' ', $sources);
        }

        if ($reportUri = config('security.csp.report_uri')) {
            $parts[] = 'report-uri '.$reportUri;
        }

        return implode('; ', $parts);
    }
}
