<meta name="csrf-token" content="{{ csrf_token() }}">
<div id="gdpr-cookie-banner" class="gdpr-cookie-banner" style="">
    <div class="gdpr-banner-content">
        <div class="gdpr-banner-text">
            <h3>{{ $title ?? 'Cookie Consent' }}</h3>
            <p>{{ $message ?? 'We use cookies to enhance your browsing experience and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.' }}</p>
        </div>
        
        <div class="gdpr-banner-actions">
            @if($showDetails ?? true)
                <button type="button" class="gdpr-btn gdpr-btn-secondary" onclick="gdprShowDetails()">
                    {{ $detailsText ?? 'Cookie Details' }}
                </button>
            @endif
            
            <button type="button" class="gdpr-btn gdpr-btn-secondary" onclick="gdprRejectAll()">
                {{ $rejectText ?? 'Reject All' }}
            </button>
            
            <button type="button" class="gdpr-btn gdpr-btn-primary" onclick="gdprAcceptAll()">
                {{ $acceptText ?? 'Accept All' }}
            </button>
            
            <button type="button" class="gdpr-btn gdpr-btn-close" onclick="gdprHideBanner()" title="Close">
                ×
            </button>
        </div>
    </div>
    
    @if($showDetails ?? true)
        <div id="gdpr-cookie-details" class="gdpr-cookie-details" style="display: none;">
            <h4>Cookie Categories</h4>
            <div class="gdpr-consent-categories">
                @foreach($consentTypes ?? [] as $consentType)
                    <div class="gdpr-consent-item">
                        <label class="gdpr-consent-label">
                            <input type="checkbox" 
                                   name="consent[{{ $consentType->slug }}]" 
                                   value="1"
                                   {{ $consentType->required ? 'checked disabled' : '' }}
                                   class="gdpr-consent-checkbox">
                            <span class="gdpr-consent-name">{{ $consentType->name }}</span>
                            @if($consentType->required)
                                <span class="gdpr-required">(Required)</span>
                            @endif
                        </label>
                        <p class="gdpr-consent-description">{{ $consentType->description }}</p>
                    </div>
                @endforeach
            </div>
            
            <div class="gdpr-details-actions">
                <button type="button" class="gdpr-btn gdpr-btn-secondary" onclick="gdprHideDetails()">
                    {{ $backText ?? 'Back' }}
                </button>
                <button type="button" class="gdpr-btn gdpr-btn-primary" onclick="gdprSavePreferences()">
                    {{ $saveText ?? 'Save Preferences' }}
                </button>
            </div>
        </div>
    @endif
</div>

<div id="gdpr-consent-icon" class="gdpr-consent-icon" style="display: none;" onclick="gdprShowBanner()">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z"/>
    </svg>
    <span>Cookie Settings</span>
</div>

<style>
.gdpr-cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    border-top: 1px solid #ddd;
    padding: 20px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
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
    color: #666;
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
    background: #007cba;
    color: white;
}

.gdpr-btn-primary:hover {
    background: #005a87;
}

.gdpr-btn-secondary {
    background: #f1f1f1;
    color: #333;
}

.gdpr-btn-secondary:hover {
    background: #e1e1e1;
}

.gdpr-cookie-details {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
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
    color: #666;
    font-weight: normal;
    font-size: 12px;
}

.gdpr-consent-description {
    margin: 5px 0 0 30px;
    color: #666;
    font-size: 14px;
}

.gdpr-details-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.gdpr-btn-close {
    background: transparent;
    color: #666;
    font-size: 20px;
    padding: 5px 10px;
    min-width: auto;
}

.gdpr-btn-close:hover {
    background: #f1f1f1;
    color: #333;
}

.gdpr-consent-icon {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #007cba;
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
    background: #005a87;
}

.gdpr-consent-icon svg {
    width: 20px;
    height: 20px;
}

@media (max-width: 768px) {
    .gdpr-banner-content {
        flex-direction: column;
        align-items: stretch;
    }
    
    .gdpr-banner-actions {
        justify-content: center;
    }
    
    .gdpr-consent-icon span {
        display: none;
    }
    
    .gdpr-consent-icon {
        padding: 12px;
        border-radius: 50%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('gdpr_consent_given')) {
        fetch('/gdpr/consent/status', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        }).then(response => response.json())
        .then(data => {
            if (data.hasAnyConsent) {
                showConsentIcon(data);
            }
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
        }
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
        }
    }).then(() => {
        localStorage.setItem('gdpr_consent_given', 'true');
        document.getElementById('gdpr-cookie-banner').style.display = 'none';
        setTimeout(() => {
            fetch('/gdpr/consent/status').then(r => r.json()).then(showConsentIcon);
        }, 100);
    });
}

function gdprRejectAll() {
    fetch('/gdpr/consent/reject-all', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    }).then(() => {
        localStorage.setItem('gdpr_consent_given', 'true');
        document.getElementById('gdpr-cookie-banner').style.display = 'none';
        setTimeout(() => {
            fetch('/gdpr/consent/status').then(r => r.json()).then(showConsentIcon);
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
        body: JSON.stringify({ consents })
    }).then(() => {
        localStorage.setItem('gdpr_consent_given', 'true');
        document.getElementById('gdpr-cookie-banner').style.display = 'none';
        setTimeout(() => {
            fetch('/gdpr/consent/status').then(r => r.json()).then(showConsentIcon);
        }, 100);
    });
}

function showConsentIcon(data) {
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
