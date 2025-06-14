<div id="gdpr-cookie-banner" class="gdpr-cookie-banner" style="display: none;">
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

@media (max-width: 768px) {
    .gdpr-banner-content {
        flex-direction: column;
        align-items: stretch;
    }
    
    .gdpr-banner-actions {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (!localStorage.getItem('gdpr_consent_given')) {
        document.getElementById('gdpr-cookie-banner').style.display = 'block';
    }
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
    });
}
</script>
