<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every response says how it may be used.
 *
 * The content security policy is the one that matters: with it, a script only
 * runs if the server put it on the page. The policy refuses inline handlers
 * (`onclick=`) outright, which is why the views carry none, and admits an inline
 * `<script>` only when it holds the nonce minted for that request.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_response_carries_the_hardening_headers(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');

        $this->assertStringContainsString('camera=()', (string) $response->headers->get('Permissions-Policy'));
    }

    #[Test]
    public function the_policy_is_enforced_and_scripts_need_the_nonce(): void
    {
        $response = $this->get(route('login'));

        $policy = (string) $response->headers->get('Content-Security-Policy');

        $this->assertNotSame('', $policy, 'The policy is enforced, not merely reported.');
        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));

        preg_match('/script-src ([^;]+)/', $policy, $scripts);

        $this->assertStringContainsString("'self'", $scripts[1]);
        $this->assertMatchesRegularExpression("/'nonce-[A-Za-z0-9]{32}'/", $scripts[1]);
        $this->assertStringNotContainsString("'unsafe-inline'", $scripts[1], 'Inline scripts without the nonce are refused.');

        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
    }

    /**
     * The nonce in the header has to be the one on the page, or the bundle itself
     * would be refused.
     */
    #[Test]
    public function the_nonce_in_the_header_is_the_one_on_the_page(): void
    {
        $response = $this->get(route('login'));

        preg_match("/'nonce-([A-Za-z0-9]{32})'/", (string) $response->headers->get('Content-Security-Policy'), $header);

        $this->assertNotEmpty($header, 'The header carries a nonce.');

        $response->assertSee('nonce="'.$header[1].'"', false);
    }

    #[Test]
    public function each_request_gets_its_own_nonce(): void
    {
        $first = (string) $this->get(route('login'))->headers->get('Content-Security-Policy');
        $second = (string) $this->get(route('login'))->headers->get('Content-Security-Policy');

        $this->assertNotSame($first, $second);
    }

    /**
     * HSTS is off by default: the server's certificate is self-signed, and a
     * browser holding an HSTS pin refuses the site with no way past the day that
     * certificate changes. It is sent only when switched on, and only over TLS.
     */
    #[Test]
    public function strict_transport_security_is_off_by_default_and_only_ever_sent_over_tls(): void
    {
        $path = parse_url(route('login'), PHP_URL_PATH);

        $this->get('https://localhost'.$path)->assertHeaderMissing('Strict-Transport-Security');

        config(['security.hsts.enabled' => true]);

        $this->get('http://localhost'.$path)->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost'.$path)->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    #[Test]
    public function the_policy_can_be_switched_to_report_only_without_a_deploy(): void
    {
        config(['security.csp.enforce' => false]);

        $response = $this->get(route('login'));

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    /**
     * Inline handlers are exactly what the policy refuses, and one creeping back in
     * would fail silently for every user — the button would simply do nothing.
     */
    #[Test]
    public function no_view_carries_an_inline_event_handler(): void
    {
        $offenders = [];

        foreach (glob(resource_path('views/**/*.blade.php')) + glob(resource_path('views/**/**/*.blade.php')) + glob(resource_path('views/**/**/**/*.blade.php')) as $view) {
            // A property set inside a nonce'd script (`reader.onload = …`) is fine;
            // only the attribute form is a handler the policy refuses.
            $markup = (string) preg_replace('/<script\b.*?<\/script>/is', '', (string) file_get_contents($view));

            if (preg_match_all('/\son(click|submit|change|load|input|keyup|keydown|mouseover)\s*=\s*["\']/i', $markup, $matches)) {
                $offenders[] = str_replace(resource_path('views/'), '', $view).' ('.count($matches[0]).')';
            }
        }

        $this->assertSame([], $offenders, "These views carry inline event handlers, which the policy refuses:\n".implode("\n", $offenders));
    }

    #[Test]
    public function every_inline_script_carries_the_nonce(): void
    {
        $offenders = [];

        foreach (glob(resource_path('views/**/*.blade.php')) + glob(resource_path('views/**/**/*.blade.php')) + glob(resource_path('views/**/**/**/*.blade.php')) as $view) {
            $source = (string) file_get_contents($view);

            if (preg_match_all('/<script\b(?![^>]*(?:@cspNonce|nonce=))[^>]*>/i', $source, $matches)) {
                $offenders[] = str_replace(resource_path('views/'), '', $view).': '.implode(' ', $matches[0]);
            }
        }

        $this->assertSame([], $offenders, "These inline scripts lack the nonce and will be refused:\n".implode("\n", $offenders));
    }

    #[Test]
    public function in_production_every_url_is_https_and_the_session_cookie_is_secure(): void
    {
        config(['security.force_https' => true, 'session.secure' => true]);
        URL::forceScheme('https');

        $this->assertStringStartsWith('https://', route('login'));

        $cookie = collect($this->get(route('login'))->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure(), 'The session cookie must not travel over plain HTTP.');
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    #[Test]
    public function the_production_defaults_are_derived_from_the_environment(): void
    {
        // config() is already built, so this reads the file the way it is read at
        // boot with APP_ENV=production and nothing else set.
        // env() reads $_SERVER and $_ENV, not putenv().
        $previous = [$_SERVER['APP_ENV'] ?? null, $_ENV['APP_ENV'] ?? null];
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'production';
        unset($_SERVER['SESSION_SECURE_COOKIE'], $_ENV['SESSION_SECURE_COOKIE'], $_SERVER['FORCE_HTTPS'], $_ENV['FORCE_HTTPS']);

        try {
            $session = require config_path('session.php');
            $security = require config_path('security.php');
        } finally {
            [$_SERVER['APP_ENV'], $_ENV['APP_ENV']] = $previous;
        }

        $this->assertTrue($session['secure']);
        $this->assertTrue($security['force_https']);
    }
}
