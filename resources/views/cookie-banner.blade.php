<meta name="csrf-token" content="{{ csrf_token() }}">
<div id="gdpr-cookie-banner" class="gdpr-cookie-banner" style="" role="region"
     aria-labelledby="gdpr-banner-title">
    <div class="gdpr-banner-content">
        <div class="gdpr-banner-text">
            <h3 id="gdpr-banner-title">{{ $title ?? config('gdpr-consent-database.text.title', 'Cookie Consent') }}</h3>
            <p>{!! $message ?? config('gdpr-consent-database.text.message', 'We use cookies to enhance your browsing experience and analyze our traffic. By clicking "Accept All", you consent to our use of cookies. For additional information, please see our <a href="cookie-policy">Cookie Policy</a> and <a href="privacy-policy">Privacy Policy</a>.') !!}</p>
        </div>
        
        <div class="gdpr-banner-actions">
            @if($showDetails ?? true)
                <button type="button" class="gdpr-btn gdpr-btn-secondary" onclick="gdprShowDetails()">
                    {{ $detailsText ?? config('gdpr-consent-database.text.details_text', 'Cookie Details') }}
                </button>
            @endif
            
            <button type="button" class="gdpr-btn gdpr-btn-secondary" onclick="gdprRejectAll()">
                {{ $rejectText ?? config('gdpr-consent-database.text.reject_text', 'Reject All') }}
            </button>
            
            <button type="button" class="gdpr-btn gdpr-btn-primary" onclick="gdprAcceptAll()">
                {{ $acceptText ?? config('gdpr-consent-database.text.accept_text', 'Accept All') }}
            </button>
            
            <button type="button" class="gdpr-btn gdpr-btn-close" onclick="gdprHideBanner()" title="Close"
                    aria-label="Close cookie banner">
                ×
            </button>
        </div>
    </div>
    
    @if($showDetails ?? true)
        <div id="gdpr-cookie-details" class="gdpr-cookie-details" style="display: none;">
            <h4>{{ config('gdpr-consent-database.text.details_header', 'Cookie Categories') }}</h4>
            <div class="gdpr-consent-categories">
                @foreach($consentTypes as $consentType)
                    <div class="gdpr-consent-item">
                        <label class="gdpr-consent-label">
                            <input type="checkbox"
                                   name="consent[{{ $consentType->slug }}]"
                                   value="1"
                                   {{ $consentType->required ? 'checked disabled' : '' }}
                                   @if($consentType->description) aria-describedby="gdpr-desc-{{ $consentType->slug }}" @endif
                                   class="gdpr-consent-checkbox">
                            <span class="gdpr-consent-name">{{ $consentType->name }}</span>
                            @if($consentType->required)
                                <span class="gdpr-required">{{ config('gdpr-consent-database.text.required_text', '(Required)') }}</span>
                            @endif
                        </label>
                        @if($consentType->description)
                            <p id="gdpr-desc-{{ $consentType->slug }}" class="gdpr-consent-description">{{ $consentType->description }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
            
            <div class="gdpr-details-actions">
                <button type="button" class="gdpr-btn gdpr-btn-secondary" onclick="gdprHideDetails()">
                    {{ $backText ?? config('gdpr-consent-database.text.back_text', 'Back') }}
                </button>
                <button type="button" class="gdpr-btn gdpr-btn-primary" onclick="gdprSavePreferences()">
                    {{ $saveText ?? config('gdpr-consent-database.text.save_text', 'Save Preferences') }}
                </button>
            </div>
        </div>
    @endif
</div>

@php
    $iconPosition = config('gdpr-consent-database.icon.position', 'right');
    $iconDisplay = config('gdpr-consent-database.icon.display', 'icon-with-text');
    $iconPositionClass = 'gdpr-icon-' . $iconPosition;
    $iconDisplayClass = 'gdpr-icon-' . str_replace('-', '_', $iconDisplay);
@endphp

<div id="gdpr-consent-icon" class="gdpr-consent-icon {{ $iconPositionClass }} {{ $iconDisplayClass }}" style="display: none;"
     role="button" tabindex="0" aria-label="{{ config('gdpr-consent-database.text.icon_text', 'Cookie Settings') }}"
     onclick="gdprShowBanner()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();gdprShowBanner();}">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z"/>
    </svg>
    <span>{{ config('gdpr-consent-database.text.icon_text', 'Cookie Settings') }}</span>
</div>

@php
    use Selli\LaravelGdprConsentDatabase\Support\Css;
    $c = fn (string $key, string $default): string => Css::color((string) config('gdpr-consent-database.'.$key, $default), $default);
@endphp
<style>
:root {
    --gdpr-banner-bg: {{ $c('colors.banner_background', '#fff') }};
    --gdpr-banner-border: {{ $c('colors.banner_border', '#ddd') }};
    --gdpr-banner-shadow: {{ $c('colors.banner_shadow', 'rgba(0,0,0,0.1)') }};
    --gdpr-text-primary: {{ $c('colors.text_primary', '#333') }};
    --gdpr-text-secondary: {{ $c('colors.text_secondary', '#666') }};
    --gdpr-btn-primary-bg: {{ $c('colors.button_primary_bg', '#007cba') }};
    --gdpr-btn-primary-hover: {{ $c('colors.button_primary_hover', '#005a87') }};
    --gdpr-btn-secondary-bg: {{ $c('colors.button_secondary_bg', '#f1f1f1') }};
    --gdpr-btn-secondary-hover: {{ $c('colors.button_secondary_hover', '#e1e1e1') }};
    --gdpr-details-border: {{ $c('colors.details_border', '#eee') }};
    --gdpr-icon-bg: {{ $c('icon.background', '#007cba') }};
    --gdpr-icon-hover: {{ $c('icon.background_hover', '#005a87') }};
}

.gdpr-cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--gdpr-banner-bg);
    border-top: 1px solid var(--gdpr-banner-border);
    padding: 20px;
    box-shadow: 0 -2px 10px var(--gdpr-banner-shadow);
    z-index: 9999;
}

.gdpr-banner-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.gdpr-banner-text h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
}

.gdpr-banner-text p {
    margin: 0;
    color: var(--gdpr-text-secondary);
}

.gdpr-banner-actions {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}

.gdpr-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s;
}

.gdpr-btn-primary {
    background: var(--gdpr-btn-primary-bg);
    color: white;
}

.gdpr-banner-text a {
    color: var(--gdpr-btn-primary-bg);
}

.gdpr-btn-primary:hover {
    background: var(--gdpr-btn-primary-hover);
}

.gdpr-btn-secondary {
    background: var(--gdpr-btn-secondary-bg);
    color: var(--gdpr-text-primary);
}

.gdpr-btn-secondary:hover {
    background: var(--gdpr-btn-secondary-hover);
}

.gdpr-cookie-details {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--gdpr-details-border);
}

.gdpr-consent-categories {
    margin: 15px 0;
}

.gdpr-consent-item {
    margin-bottom: 15px;
}

.gdpr-consent-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: bold;
}

.gdpr-required {
    color: var(--gdpr-text-secondary);
    font-weight: normal;
    font-size: 12px;
}

.gdpr-consent-description {
    margin: 5px 0 0 30px;
    color: var(--gdpr-text-secondary);
    font-size: 14px;
}

.gdpr-details-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.gdpr-btn-close {
    background: transparent;
    color: var(--gdpr-text-secondary);
    font-size: 20px;
    padding: 5px 10px;
    min-width: auto;
}

.gdpr-btn-close:hover {
    background: var(--gdpr-btn-secondary-bg);
    color: var(--gdpr-text-primary);
}

.gdpr-consent-icon {
    position: fixed;
    background: var(--gdpr-icon-bg);
    color: white;
    padding: 12px 16px;
    border-radius: 25px;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    z-index: 9998;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    transition: background-color 0.2s;
}

.gdpr-consent-icon:hover {
    background: var(--gdpr-icon-hover);
}

.gdpr-consent-icon svg {
    width: 20px;
    height: 20px;
}

/* Icon positioning */
.gdpr-icon-right {
    bottom: 20px;
    right: 20px;
}

.gdpr-icon-left {
    bottom: 20px;
    left: 20px;
}

.gdpr-icon-top {
    top: 20px;
    right: 20px;
}

.gdpr-icon-bottom {
    bottom: 20px;
    right: 20px;
}

/* Icon display options */
.gdpr-icon-icon_only span {
    display: none;
}

.gdpr-icon-icon_only {
    padding: 12px;
    border-radius: 50%;
}

.gdpr-icon-icon_with_text span {
    display: inline;
}

@media (max-width: 768px) {
    .gdpr-banner-content {
        flex-direction: column;
        align-items: stretch;
    }
    
    .gdpr-banner-actions {
        justify-content: center;
    }
    
    .gdpr-icon-icon_with_text span {
        display: none;
    }
    
    .gdpr-icon-icon_with_text {
        padding: 12px;
        border-radius: 50%;
    }
}
</style>

@php
    $gdprRouteName = config('gdpr-consent-database.routes.name', 'gdpr.consent.');
    // Guard against routes being disabled (config routes.enabled=false): resolving an unregistered
    // named route would throw and 500 the whole page. Fall back to an empty object instead.
    $gdprUrls = \Illuminate\Support\Facades\Route::has($gdprRouteName.'accept-all')
        ? [
            'acceptAll' => route($gdprRouteName.'accept-all'),
            'rejectAll' => route($gdprRouteName.'reject-all'),
            'savePreferences' => route($gdprRouteName.'save-preferences'),
            'status' => route($gdprRouteName.'status'),
        ]
        : new \stdClass;
@endphp
<script>
const gdprUrls = @json($gdprUrls);

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function setCookie(name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
}

function getTechnicalCookieCode() {
    return getCookie('gdpr_session_id') || generateSessionId();
}

function generateSessionId() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return 'gdpr_' + window.crypto.randomUUID();
    }
    return 'gdpr_' + Math.random().toString(36).slice(2, 11) + '_' + Date.now();
}

document.addEventListener('DOMContentLoaded', function() {
    const technicalCookieCode = getTechnicalCookieCode();
    setCookie('gdpr_session_id', technicalCookieCode, 30);
    
    if (getCookie('gdpr_consent_given')) {
        document.getElementById('gdpr-cookie-banner').style.display = 'none';
        fetch(gdprUrls.status, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin',
            body: JSON.stringify({ technical_cookie_code: technicalCookieCode })
        }).then(response => response.json())
        .then(data => {
            showConsentIcon(data);
        }).catch(() => {
            showConsentIcon({});
        });
        return;
    }
    
    fetch(gdprUrls.status, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin',
        body: JSON.stringify({ technical_cookie_code: technicalCookieCode })
    }).then(response => response.json())
    .then(data => {
        if (!data.hasAnyConsent) {
            document.getElementById('gdpr-cookie-banner').style.display = 'block';
        } else {
            showConsentIcon(data);
        }
    }).catch(() => {
        document.getElementById('gdpr-cookie-banner').style.display = 'block';
    });
});

function gdprAcceptAll() {
    const technicalCookieCode = getTechnicalCookieCode();
    setCookie('gdpr_session_id', technicalCookieCode, 30);
    
    fetch(gdprUrls.acceptAll, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin',
        body: JSON.stringify({ technical_cookie_code: technicalCookieCode })
    }).then(() => {
        setCookie('gdpr_consent_given', 'true', 30);
        document.getElementById('gdpr-cookie-banner').style.display = 'none';
        setTimeout(() => {
            fetch(gdprUrls.status, { 
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                },
                credentials: 'same-origin',
                body: JSON.stringify({ technical_cookie_code: technicalCookieCode })
            }).then(r => r.json()).then(showConsentIcon);
        }, 100);
    });
}

function gdprRejectAll() {
    const technicalCookieCode = getTechnicalCookieCode();
    setCookie('gdpr_session_id', technicalCookieCode, 30);
    
    fetch(gdprUrls.rejectAll, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin',
        body: JSON.stringify({ technical_cookie_code: technicalCookieCode })
    }).then(() => {
        setCookie('gdpr_consent_given', 'true', 30);
        document.getElementById('gdpr-cookie-banner').style.display = 'none';
        setTimeout(() => {
            fetch(gdprUrls.status, { 
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                },
                credentials: 'same-origin',
                body: JSON.stringify({ technical_cookie_code: technicalCookieCode })
            }).then(r => r.json()).then(showConsentIcon);
        }, 100);
    });
}

function gdprShowDetails() {
    document.getElementById('gdpr-cookie-details').style.display = 'block';
}

function gdprHideDetails() {
    document.getElementById('gdpr-cookie-details').style.display = 'none';
}

function gdprSavePreferences() {
    const technicalCookieCode = getTechnicalCookieCode();
    setCookie('gdpr_session_id', technicalCookieCode, 30);
    
    const checkboxes = document.querySelectorAll('.gdpr-consent-checkbox');
    const consents = {};
    
    checkboxes.forEach(checkbox => {
        const slug = checkbox.name.match(/consent\[(.+)\]/)[1];
        consents[slug] = checkbox.checked;
    });
    
    fetch(gdprUrls.savePreferences, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin',
        body: JSON.stringify({ consents, technical_cookie_code: technicalCookieCode })
    }).then(() => {
        setCookie('gdpr_consent_given', 'true', 30);
        document.getElementById('gdpr-cookie-banner').style.display = 'none';
        setTimeout(() => {
            fetch(gdprUrls.status, { 
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                },
                credentials: 'same-origin',
                body: JSON.stringify({ technical_cookie_code: technicalCookieCode })
            }).then(r => r.json()).then(showConsentIcon);
        }, 100);
    });
}

function showConsentIcon(data) {
    document.getElementById('gdpr-consent-icon').style.display = 'flex';
    window.gdprCurrentConsents = data.consents;
}

function gdprShowBanner() {
    const banner = document.getElementById('gdpr-cookie-banner');
    banner.style.display = 'block';
    document.getElementById('gdpr-consent-icon').style.display = 'none';

    // Move keyboard focus into the banner for screen-reader / keyboard users.
    banner.setAttribute('tabindex', '-1');
    banner.focus();

    if (window.gdprCurrentConsents) {
        Object.keys(window.gdprCurrentConsents).forEach(slug => {
            const checkbox = document.querySelector(`input[name="consent[${slug}]"]`);
            if (checkbox && !checkbox.disabled) {
                checkbox.checked = window.gdprCurrentConsents[slug];
            }
        });
    }
}

function gdprHideBanner() {
    document.getElementById('gdpr-cookie-banner').style.display = 'none';
    if (window.gdprCurrentConsents) {
        document.getElementById('gdpr-consent-icon').style.display = 'flex';
    }
}
</script>
