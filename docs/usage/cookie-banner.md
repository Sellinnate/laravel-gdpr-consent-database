---
title: "Cookie Banner"
description: "The built-in, configurable cookie consent banner."
---

# Cookie Banner

The package ships a Blade cookie banner that lists your `cookie`-category consent types and posts the
visitor's choices to built-in AJAX endpoints.

## Adding the banner

Place the directive in your layout, before `</body>`:

```blade
@gdprCookieBanner
```

Expose the CSRF token so the AJAX calls work:

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

That's it — the banner appears for visitors who haven't decided yet, lists every **active** consent type
with `category = 'cookie'`, and records their choice.

## Passing options inline

Any text option can be overridden per-invocation (takes precedence over config):

```blade
@gdprCookieBanner([
    'title'      => 'Cookie Preferences',
    'message'    => 'We use cookies to improve your experience.',
    'acceptText' => 'Accept All Cookies',
    'rejectText' => 'Reject Optional',
])
```

## What the buttons do

| Button | Endpoint | Effect |
|---|---|---|
| Accept All | `POST /gdpr/consent/accept-all` | Grants every active cookie consent type |
| Reject All | `POST /gdpr/consent/reject-all` | Grants required cookies, **revokes** optional ones |
| Save Preferences | `POST /gdpr/consent/save-preferences` | Grants checked, revokes unchecked |
| (status check) | `POST /gdpr/consent/status` | Returns the current consent state |

::: callout success "Reject All actually rejects"
`Reject All` revokes any optional cookie consent the visitor previously granted — it does not silently keep
them. This is a hard GDPR requirement (withdrawal must be as easy as giving consent — Art. 7(3)).
:::

## Customising look & feel

All texts, colours and the floating settings icon are configurable. See
[Configuration](/configuration/overview) for every option.

To customise the markup itself, publish the view:

```bash
php artisan vendor:publish --tag="gdpr-consent-database-views"
```

It is published to `resources/views/vendor/gdpr-consent-database/cookie-banner.blade.php`.

## ePrivacy note: strictly-necessary cookies

Under the ePrivacy Directive, strictly-necessary cookies do not require consent and **non-essential cookies
must be blocked until the visitor opts in**. This package records the visitor's *choice*; it does not, by
itself, block third-party scripts. Gate your non-essential scripts on the consent state, for example:

```blade
@if($user?->hasConsent('analytics-cookies'))
    <!-- analytics script -->
@endif
```

## Accessibility

The banner ships with sensible accessibility defaults:

- the banner is a labelled `role="region"` (`aria-labelledby` the title);
- the close button and the floating settings icon have `aria-label`s;
- the settings icon is keyboard-operable (`Enter` / `Space`);
- each cookie checkbox is associated with its description via `aria-describedby`;
- opening the banner moves keyboard focus into it.

## Privacy

IP addresses captured when consent is recorded honour the [privacy configuration](/configuration/overview):
you can disable IP storage entirely or store an anonymised (masked) form.

## Routes

The endpoints are registered automatically under the configurable `gdpr/consent` prefix and the banner's
JavaScript resolves them from the route configuration (so changing the prefix just works). They rely on the
`web` middleware group (session + CSRF), so ensure your banner is rendered within a `web` route.
