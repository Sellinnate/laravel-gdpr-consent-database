<?php

namespace Selli\LaravelGdprConsentDatabase\Database\Seeders;

use Illuminate\Database\Seeder;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

class CookieConsentSeeder extends Seeder
{
    public function run()
    {
        $cookieConsents = [
            [
                'name' => 'Technical Cookies',
                'slug' => 'technical-cookies',
                'description' => 'Essential cookies required for the website to function properly',
                'required' => true,
                'active' => true,
                'category' => 'cookie',
                'version' => '1.0',
            ],
            [
                'name' => 'Profiling Cookies',
                'slug' => 'profiling-cookies',
                'description' => 'Cookies used to create profiles and show personalized content',
                'required' => false,
                'active' => true,
                'category' => 'cookie',
                'version' => '1.0',
            ],
            [
                'name' => 'Tracking Cookies',
                'slug' => 'tracking-cookies',
                'description' => 'Cookies used to track user behavior and website analytics',
                'required' => false,
                'active' => true,
                'category' => 'cookie',
                'version' => '1.0',
            ],
        ];

        foreach ($cookieConsents as $consent) {
            ConsentType::updateOrCreate(
                ['slug' => $consent['slug']],
                $consent
            );
        }
    }
}
