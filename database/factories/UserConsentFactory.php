<?php

namespace Selli\LaravelGdprConsentDatabase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

class UserConsentFactory extends Factory
{
    protected $model = UserConsent::class;

    public function definition()
    {
        return [
            'consent_type_id' => ConsentType::factory(),
            'granted' => $this->faker->boolean(80), // 80% di probabilità che sia concesso
            'granted_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'revoked_at' => function (array $attributes) {
                // Se il consenso è stato concesso, c'è una probabilità del 20% che sia stato revocato
                return $attributes['granted'] ? 
                    ($this->faker->boolean(20) ? $this->faker->dateTimeBetween($attributes['granted_at'], 'now') : null) : 
                    null;
            },
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'metadata' => null,
        ];
    }

    /**
     * Indica che il consenso è stato concesso.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function granted()
    {
        return $this->state(function (array $attributes) {
            return [
                'granted' => true,
                'granted_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'revoked_at' => null,
            ];
        });
    }

    /**
     * Indica che il consenso è stato revocato.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function revoked()
    {
        return $this->state(function (array $attributes) {
            $grantedAt = $attributes['granted_at'] ?? $this->faker->dateTimeBetween('-1 year', '-1 day');
            
            return [
                'granted' => false,
                'granted_at' => $grantedAt,
                'revoked_at' => $this->faker->dateTimeBetween($grantedAt, 'now'),
            ];
        });
    }

    /**
     * Imposta i metadati per il consenso.
     *
     * @param array $metadata
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withMetadata(array $metadata)
    {
        return $this->state(function (array $attributes) use ($metadata) {
            return [
                'metadata' => $metadata,
            ];
        });
    }
}
