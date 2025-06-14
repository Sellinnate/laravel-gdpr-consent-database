# Laravel GDPR Consent Database

[![Latest Version on Packagist](https://img.shields.io/packagist/v/selli/laravel-gdpr-consent-database.svg?style=flat-square)](https://packagist.org/packages/selli/laravel-gdpr-consent-database)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/sellinnate/laravel-gdpr-consent-database/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/sellinnate/laravel-gdpr-consent-database/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/sellinnate/laravel-gdpr-consent-database/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/sellinnate/laravel-gdpr-consent-database/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/selli/laravel-gdpr-consent-database.svg?style=flat-square)](https://packagist.org/packages/selli/laravel-gdpr-consent-database)

A comprehensive Laravel package for managing GDPR consent in your applications. This package provides a complete solution for tracking user consents, managing consent types, and ensuring GDPR compliance.

## Installation

You can install the package via composer:

```bash
composer require selli/laravel-gdpr-consent-database
```

## Consent Categories

The package supports categorizing consent types to handle different types of consents appropriately:

- `cookie` - For cookie-related consents (technical, profiling, tracking, etc.)
- `other` - For non-cookie consents (marketing, newsletters, etc.)

### Cookie Banner Integration

The cookie banner automatically filters and displays only consent types with category `'cookie'`. Other consent types should be managed through your application's registration/preference flows.

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="gdpr-consent-database-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="gdpr-consent-database-config"
```

This is the contents of the published config file:

```php
return [
    'text' => [
        'title' => 'Cookie Consent',
        'message' => 'We use cookies to enhance your browsing experience and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.',
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
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-gdpr-consent-database-views"
```

## Usage

### Setup

After installing the package and running the migrations, you need to add the `HasGdprConsents` trait to your User model or any other model that needs to manage GDPR consents:

```php
use Selli\LaravelGdprConsentDatabase\Traits\HasGdprConsents;

class User extends Authenticatable
{
    use HasGdprConsents;
    
    // ... rest of your model
}
```

### Creating Consent Types

First, you need to create consent types that users can agree to:

```php
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

// Create a required consent type (e.g., Terms and Conditions)
ConsentType::create([
    'name' => 'Terms and Conditions',
    'slug' => 'terms',
    'description' => 'Agreement to the website terms and conditions',
    'required' => true,
    'active' => true,
    'category' => 'other',
]);

// Create cookie-related consent
ConsentType::create([
    'name' => 'Technical Cookies',
    'slug' => 'technical-cookies',
    'description' => 'Essential cookies required for website functionality',
    'required' => true,
    'active' => true,
    'category' => 'cookie',
]);

// Create non-cookie consent
ConsentType::create([
    'name' => 'Marketing Emails',
    'slug' => 'marketing-emails', 
    'description' => 'Consent to receive marketing communications',
    'required' => false,
    'active' => true,
    'category' => 'other',
]);
```

### Managing User Consents

Once you have set up consent types, you can manage user consents:

```php
// Get the authenticated user
$user = auth()->user();

// Check if the user has given a specific consent
if ($user->hasConsent('marketing-emails')) {
    // User has consented to marketing emails
}

// Give consent (can use slug or consent type ID)
$user->giveConsent('marketing-emails');

// Give consent with additional metadata
$user->giveConsent('marketing-emails', [
    'source' => 'registration_form',
    'version' => '1.0',
]);

// Revoke consent
$user->revokeConsent('marketing-emails');

// Get all active consents for the user
$activeConsents = $user->activeConsents();

// Check if the user has all required consents
if ($user->hasAllRequiredConsents()) {
    // User has all required consents
} else {
    // Get missing required consents
    $missingConsents = $user->getMissingRequiredConsents();
}

### Consent Versioning

#### Overview

The package supports versioning of consent types, which is essential for GDPR compliance when terms or policies change.

#### Usage

```php
// Create a consent type with version
$consentType = ConsentType::create([
    'name' => 'Privacy Policy',
    'slug' => 'privacy-policy',
    'description' => 'Privacy Policy consent',
    'required' => true,
    'active' => true,
    'version' => '1.0',
]);

// Create a new version when terms change
$newVersion = $consentType->createNewVersion([
    'description' => 'Updated Privacy Policy consent',
]);

// Check if a user's consent is for the current version
if ($user->hasConsent('privacy-policy', true)) {
    // User has consented to the current version
} else {
    // User needs to renew consent for the new version
}

// Get all consents that need renewal (expired or outdated version)
$consentsNeedingRenewal = $user->consentsNeedingRenewal();

// Renew a consent with the latest version
$user->renewConsent('privacy-policy');
```

### Consent Expiration

#### Overview

The package also supports consent expiration, allowing you to set validity periods for consents.

#### Usage

```php
// Create a consent type with a validity period (in months)
ConsentType::create([
    'name' => 'Marketing Emails',
    'slug' => 'marketing-emails',
    'description' => 'Consent to receive marketing emails',
    'required' => false,
    'active' => true,
    'category' => 'other',
    'version' => '1.0',
    'validity_months' => 12, // Consent valid for 12 months
]);

// Give consent with a custom validity period
$user->giveConsent('marketing-emails', [], 6); // Valid for 6 months

// Check if a consent is expired
$consent = $user->consents()->where('consent_type_id', $consentTypeId)->first();
if ($consent->isExpired()) {
    // Consent is expired
}

// Get all expired consents
$expiredConsents = $user->expiredConsents();

// Get consents that are about to expire within the next 30 days
$soonExpiringConsents = $user->getConsentsExpiringWithinDays(30);
```

### Guest Consent Management

For non-logged-in users, the package provides session-based consent management using technical cookie codes:

```php
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;

$guestManager = new GuestConsentManager();

// Give consent for a guest user (uses current session)
$guestManager->giveConsent('marketing-emails', [
    'source' => 'cookie_banner',
]);

// Check if guest has given consent
if ($guestManager->hasConsent('marketing-emails')) {
    // Guest has consented to marketing emails
}

// Get all active consents for guest
$activeConsents = $guestManager->getActiveConsents();

// Check if guest has all required consents
if ($guestManager->hasAllRequiredConsents()) {
    // Guest has all required consents
}

// Work with specific technical cookie code (session ID)
$technicalCookieCode = 'gdpr_abc123_1234567890';
$guestManager->giveConsent('terms', [], null, $technicalCookieCode);

// Revoke guest consent
$guestManager->revokeConsent('marketing-emails', $technicalCookieCode);
```

The guest consent system uses the `guest_consents` table to track session information and links to `user_consents` using the technical cookie code as the session identifier.

### Using the Cookie Consent Seeder

Run the seeder to populate default cookie consent types:

```bash
php artisan db:seed --class="Selli\LaravelGdprConsentDatabase\Database\Seeders\CookieConsentSeeder"
```

### Cookie Banner Integration

The package provides a blade directive for displaying cookie consent banners:

```blade
{{-- Basic usage --}}
@gdprCookieBanner

{{-- With custom options --}}
@gdprCookieBanner([
    'title' => 'Cookie Preferences',
    'message' => 'We use cookies to improve your experience.',
    'acceptText' => 'Accept All Cookies',
    'rejectText' => 'Reject Optional',
    'consentTypes' => $consentTypes
])
```

#### Publishing Views

You can publish and customize the cookie banner view:

```bash
php artisan vendor:publish --tag="laravel-gdpr-consent-database-views"
```

This will publish the view to `resources/views/vendor/gdpr-consent-database/cookie-banner.blade.php`.

#### Customization Options

The cookie banner supports extensive customization through the config file:

- **Text customization**: All button labels, messages, and UI text can be customized
- **Color theming**: Complete color scheme customization including backgrounds, borders, and button colors
- **Icon positioning**: Configure the consent settings icon position (right, left, top, bottom)
- **Icon display**: Choose between icon-only or icon-with-text display modes
- **Responsive design**: Automatic mobile-friendly adaptations

#### JavaScript Integration

The cookie banner includes built-in JavaScript for handling user interactions. It automatically:

- Shows the banner for users who haven't given consent
- Handles accept/reject actions via AJAX
- Stores consent state using cookies (`gdpr_consent_given`, `gdpr_session_id`)
- Provides detailed consent management with expandable categories
- Shows a consent settings icon after initial consent is given
- Manages technical cookie codes for session-based guest consent tracking
- Automatically checks consent status on page load and shows appropriate UI

#### Routes

The package automatically registers these routes for consent management:

- `POST /gdpr/consent/accept-all` - Accept all consent types
- `POST /gdpr/consent/reject-all` - Accept only required consents
- `POST /gdpr/consent/save-preferences` - Save specific consent preferences
- `POST /gdpr/consent/status` - Get current consent status for the session

Make sure to include the CSRF token in your layout:

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

## Database Structure

This package creates three main tables:

### consent_types

Stores the different types of consent that can be requested from users:

- `id`: Primary key
- `name`: Name of the consent type
- `slug`: Unique slug for easy reference
- `description`: Detailed description of what the user is consenting to
- `required`: Boolean indicating if this consent is required
- `active`: Boolean indicating if this consent type is currently active
- `version`: Version string for consent type versioning (default: '1.0')
- `validity_months`: Number of months the consent remains valid (nullable)
- `effective_from`: Timestamp when this version becomes effective (nullable)
- `effective_until`: Timestamp when this version expires (nullable)
- `category`: Category of consent ('cookie' or 'other', default: 'other')
- `metadata`: JSON field for additional data (e.g., legal references)
- `timestamps`: Created and updated timestamps

### user_consents

Stores the actual user consents:

- `id`: Primary key
- `consentable_id` and `consentable_type`: Polymorphic relationship to the user model
- `consent_type_id`: Foreign key to the consent type
- `consent_version`: Version of the consent type when consent was given (nullable)
- `granted`: Boolean indicating if consent is currently granted
- `granted_at`: Timestamp when consent was granted
- `revoked_at`: Timestamp when consent was revoked (if applicable)
- `expires_at`: Timestamp when consent expires (nullable)
- `ip_address`: IP address from which consent was given
- `user_agent`: User agent from which consent was given
- `metadata`: JSON field for additional data
- `timestamps`: Created and updated timestamps

### guest_consents

Stores guest user information for session-based consent tracking:

- `session_id`: Primary key (technical cookie code/session ID)
- `ip_address`: IP address of the guest user
- `user_agent`: User agent of the guest user
- `metadata`: JSON field for additional data
- `timestamps`: Created and updated timestamps

This table works in conjunction with `user_consents` where guest consents are stored using the technical cookie code as the `consentable_id` with `consentable_type` set to the guest consent model.

## Extending the Package

### Custom Consent Types

You can extend the `ConsentType` model to add custom functionality:

```php
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

class MyConsentType extends ConsentType
{
    // Add custom methods or override existing ones
    
    public function isLegallyRequired()
    {
        return $this->required && isset($this->metadata['legal_basis']);
    }
}
```

### Custom Consent Workflows

You can create custom consent workflows by extending the `HasGdprConsents` trait or by creating service classes that use the provided models:

```php
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

class ConsentService
{
    public function processRegistrationConsents(User $user, array $consentData)
    {
        // Process multiple consents at once
        foreach ($consentData as $slug => $isGranted) {
            if ($isGranted) {
                $user->giveConsent($slug, ['source' => 'registration']);
            }
        }
        
        // Check if all required consents are given
        return $user->hasAllRequiredConsents();
    }
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Filippo Calabrese](https://github.com/filippocalabrese)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
