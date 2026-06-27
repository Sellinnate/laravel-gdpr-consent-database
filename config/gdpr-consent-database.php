<?php

// config for Selli/LaravelGdprConsentDatabase
return [
    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The package registers the guest cookie-consent endpoints. You can disable
    | them entirely, change the URL prefix, or adjust the middleware stack.
    | The endpoints rely on session + CSRF, so keep the `web` middleware group
    | (or an equivalent) unless you provide your own.
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'gdpr/consent',
        'name' => 'gdpr.consent.',
        // `web` provides session + CSRF; `throttle` rate-limits the public consent endpoints.
        'middleware' => ['web', 'throttle:60,1'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy (data minimisation — GDPR Art. 5(1)(c) & Art. 25)
    |--------------------------------------------------------------------------
    |
    | IP address and user agent are personal data captured as part of the consent
    | proof. There is a deliberate trade-off:
    |
    | - Storing the FULL IP strengthens the Art. 7(1) proof ("from where the
    |   consent was given") — this is the default.
    | - Privacy-by-default (Art. 25) may favour minimisation. If your DPIA prefers
    |   it, enable `anonymize_ip` (masks the last IPv4 octet / the last 80 bits of
    |   an IPv6 address) or disable `store_ip_address` entirely.
    |
    | The user agent is less evidentially critical; set `store_user_agent` to
    | false to omit it.
    */
    'privacy' => [
        'store_ip_address' => true,
        'anonymize_ip' => false,
        'store_user_agent' => true,
    ],

    'text' => [
        'title' => 'Cookie Consent',
        'message' => 'We use cookies to enhance your browsing experience and analyze our traffic. By clicking "Accept All", you consent to our use of cookies. For additional information, please see our <a href="cookie-policy" target="_blank">Cookie Policy</a> and <a href="privacy-policy" target="_blank">Privacy Policy</a>.',
        'accept_text' => 'Accept All',
        'reject_text' => 'Reject All',
        'details_text' => 'Cookie Details',
        'back_text' => 'Back',
        'save_text' => 'Save Preferences',
        'icon_text' => 'Cookie Settings',
        'details_header' => 'Cookie Categories',
        'required_text' => '(Required)',
    ],

    'colors' => [
        'banner_background' => '#fff',
        'banner_border' => '#ddd',
        'banner_shadow' => 'rgba(0,0,0,0.1)',
        'text_primary' => '#333',
        'text_secondary' => '#666',
        'button_primary_bg' => '#007cba',
        'button_primary_hover' => '#005a87',
        'button_secondary_bg' => '#f1f1f1',
        'button_secondary_hover' => '#e1e1e1',
        'details_border' => '#eee',
    ],

    'icon' => [
        'position' => 'right',
        'display' => 'icon-with-text',
        'background' => '#007cba',
        'background_hover' => '#005a87',
    ],
];
