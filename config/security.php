<?php

/*
 * The headers every response carries, and the content security policy.
 *
 * The policy is data, so tightening it is an edit here rather than a code change.
 * `enforce` is the switch between reporting and refusing: a browser told to report
 * logs a violation and loads the page anyway, which is how a new rule is tried
 * against real traffic before it is allowed to break anything.
 */
return [
    /*
     * Every generated URL uses https, and a request that arrived over plain HTTP
     * is redirected. The web server should do this too; this is the guarantee
     * that holds if it does not.
     */
    'force_https' => (bool) env('FORCE_HTTPS', env('APP_ENV') === 'production'),

    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-Permitted-Cross-Domain-Policies' => 'none',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
    ],

    /*
     * Off unless switched on. The server uses a self-signed certificate, and a
     * browser that has cached an HSTS pin refuses the site outright — with no
     * click-through — the day that certificate is regenerated. Turn this on only
     * once the certificate comes from a CA the company's machines trust.
     */
    'hsts' => [
        'enabled' => (bool) env('HSTS_ENABLED', false),
        'max_age' => 31536000,
        'include_subdomains' => true,
    ],

    'csp' => [
        'enabled' => (bool) env('CSP_ENABLED', true),
        'enforce' => (bool) env('CSP_ENFORCE', true),

        /*
         * `'unsafe-eval'` is what Alpine needs to evaluate the expressions in
         * `x-data` and friends; the CSP build of Alpine forbids inline expressions
         * altogether, which would mean rewriting every one of them. Scripts are
         * otherwise allowed only from this origin or with the per-request nonce.
         *
         * `style-src` still allows inline styles: fifty-seven `style=` attributes
         * remain in the views, and Tom Select and the charts set styles at runtime.
         */
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce'", "'unsafe-eval'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'blob:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'", 'ws:', 'wss:'],
            'frame-src' => ["'none'"],
            'frame-ancestors' => ["'none'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
        ],

        'report_uri' => env('CSP_REPORT_URI'),
    ],
];
