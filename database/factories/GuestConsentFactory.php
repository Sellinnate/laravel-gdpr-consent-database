<?php

namespace Selli\LaravelGdprConsentDatabase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Selli\LaravelGdprConsentDatabase\Models\GuestConsent;

/**
 * @extends Factory<GuestConsent>
 */
class GuestConsentFactory extends Factory
{
    /** @var class-string<GuestConsent> */
    protected $model = GuestConsent::class;

    public function definition(): array
    {
        return [
            'session_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'metadata' => [
                'source' => 'test',
                'created_via' => 'factory',
            ],
        ];
    }
}
