<meta name="csrf-token" content="{{ csrf_token() }}">
<div id="gdpr-cookie-banner" class="gdpr-cookie-banner" style="">
    <div class="gdpr-banner-content">
        <div class="gdpr-banner-text">
            <h3>{{ $title ?? config('gdpr-consent-database.text.title', 'Cookie Consent') }}</h3>
            <p>{{ $message ?? config('gdpr-consent-database.text.message', 'We use cookies to enhance your browsing experience and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.') }}</p>
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
            
            <button type="button" class="gdpr-btn gdpr-btn-close" onclick="gdprHideBanner()" title="Close">
                ×
            </button>
        </div>
    </div>
    
    @if($showDetails ?? true)
        <div id="gdpr-cookie-details" class="gdpr-cookie-details" style="display: none;">
            <h4>{{ config('gdpr-consent-database.text.details_header', 'Cookie Categories') }}</h4>
            <div class="gdpr-consent-categories">
                @foreach(Selli\LaravelGdprConsentDatabase\Models\ConsentType::cookies() as $consentType)
                    <div class="gdpr-consent-item">
                        <label class="gdpr-consent-label">
                            <input type="checkbox" 
                                   name="consent[{{ $consentType->slug }}]" 
                                   value="1"
                                   {{ $consentType->required ? 'checked disabled' : '' }}
                                   class="gdpr-consent-checkbox">
                            <span class="gdpr-consent-name">{{ $consentType->name }}</span>
                            @if($consentType->required)
                                <span class="gdpr-required">{{ config('gdpr-consent-database.text.required_text', '(Required)') }}</span>
                            @endif
                        </label>
                        <p class="gdpr-consent-description">{{ $consentType->description }}</p>
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

<div id="gdpr-consent-icon" class="gdpr-consent-icon {{ $iconPositionClass }} {{ $iconDisplayClass }}" style="display: none;" onclick="gdprShowBanner()">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z"/>
    </svg>
    <span>{{ config('gdpr-consent-database.text.icon_text', 'Cookie Settings') }}</span>
</div>

<style>
:root {
    --gdpr-banner-bg: {{ config('gdpr-consent-database.colors.banner_background', '#fff') }};
    --gdpr-banner-border: {{ config('gdpr-consent-database.colors.banner_border', '#ddd') }};
    --gdpr-banner-shadow: {{ config('gdpr-consent-database.colors.banner_shadow', 'rgba(0,0,0,0.1)') }};
    --gdpr-text-primary: {{ config('gdpr-consent-database.colors.text_primary', '#333') }};
    --gdpr-text-secondary: {{ config('gdpr-consent-database.colors.text_secondary', '#666') }};
    --gdpr-btn-primary-bg: {{ config('gdpr-consent-database.colors.button_primary_bg', '#007cba') }};
    --gdpr-btn-primary-hover: {{ config('gdpr-consent-database.colors.button_primary_hover', '#005a87') }};
    --gdpr-btn-secondary-bg: {{ config('gdpr-consent-database.colors.button_secondary_bg', '#f1f1f1') }};
    --gdpr-btn-secondary-hover: {{ config('gdpr-consent-database.colors.button_secondary_hover', '#e1e1e1') }};
    --gdpr-details-border: {{ config('gdpr-consent-database.colors.details_border', '#eee') }};
    --gdpr-icon-bg: {{ config('gdpr-consent-database.icon.background', '#007cba') }};
    --gdpr-icon-hover: {{ config('gdpr-consent-database.icon.background_hover', '#005a87') }};
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

<script>
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

document.addEventListener('DOMContentLoaded', function() {
    console.log("script called");
    if (localStorage.getItem('gdpr_consent_given')) {
        console.log("consent given");
        document.getElementById('gdpr-cookie-banner').style.display = 'none';
        fetch('/gdpr/consent/status', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin' // Importante per inviare i cookie
        }).then(response => response.json())
        .then(data => {
            showConsentIcon(data);
        }).catch(() => {
            showConsentIcon({});
        });
        return;
    }
    
    fetch('/gdpr/consent/status', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin' // Importante per inviare i cookie
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
    fetch('/gdpr/consent/accept-all', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin' // Importante per inviare i cookie
    }).then(() => {
        localStorage.setItem('gdpr_consent_given', 'true');
        document.getElementById('gdpr-cookie-banner').style.display = 'none';
        setTimeout(() => {
            fetch('/gdpr/consent/status', { credentials: 'same-origin' }).then(r => r.json()).then(showConsentIcon);
        }, 100);
    });
}

function gdprRejectAll() {
    fetch('/gdpr/consent/reject-all', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin' // Importante per inviare i cookie
    }).then(() => {
        localStorage.setItem('gdpr_consent_given', 'true');
        document.getElementById('gdpr-cookie-banner').style.display = 'none';
        setTimeout(() => {
            fetch('/gdpr/consent/status', { credentials: 'same-origin' }).then(r => r.json()).then(showConsentIcon);
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
    const checkboxes = document.querySelectorAll('.gdpr-consent-checkbox');
    const consents = {};
    
    checkboxes.forEach(checkbox => {
        const slug = checkbox.name.match(/consent\[(.+)\]/)[1];
        consents[slug] = checkbox.checked;
    });
    
    fetch('/gdpr/consent/save-preferences', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin', // Importante per inviare i cookie
        body: JSON.stringify({ consents })
    }).then(() => {
        localStorage.setItem('gdpr_consent_given', 'true');
        document.getElementById('gdpr-cookie-banner').style.display = 'none';
        setTimeout(() => {
            fetch('/gdpr/consent/status', { credentials: 'same-origin' }).then(r => r.json()).then(showConsentIcon);
        }, 100);
    });
}

function showConsentIcon(data) {
    console.log("showConsentIcon called");
    document.getElementById('gdpr-consent-icon').style.display = 'flex';
    window.gdprCurrentConsents = data.consents;
}

function gdprShowBanner() {
    document.getElementById('gdpr-cookie-banner').style.display = 'block';
    document.getElementById('gdpr-consent-icon').style.display = 'none';
    
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
