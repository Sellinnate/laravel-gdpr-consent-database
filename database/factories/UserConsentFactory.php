<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;

/**
 * @extends Factory<UserConsent>
 */
class UserConsentFactory extends Factory
{
    /** @var class-string<UserConsent> */
    protected $model = UserConsent::class;

    public function definition(): array
    {
        return [
            'consent_type_id' => ConsentType::factory(),
            'granted' => $this->faker->boolean(80),
            'granted_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'revoked_at' => function (array $attributes) {
                // A granted consent has a 20% chance of having been revoked afterwards.
                return $attributes['granted']
                    ? ($this->faker->boolean(20) ? $this->faker->dateTimeBetween($attributes['granted_at'], 'now') : null)
                    : null;
            },
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the consent has been granted and not revoked.
     */
    public function granted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'granted' => true,
            'granted_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'revoked_at' => null,
        ]);
    }

    /**
     * Indicate that the consent has been revoked.
     */
    public function revoked(): static
    {
        return $this->state(function (array $attributes): array {
            $grantedAt = $attributes['granted_at'] ?? $this->faker->dateTimeBetween('-1 year', '-1 day');

            return [
                'granted' => false,
                'granted_at' => $grantedAt,
                'revoked_at' => $this->faker->dateTimeBetween($grantedAt, 'now'),
            ];
        });
    }

    /**
     * Attach metadata to the consent.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes): array => ['metadata' => $metadata]);
    }
}
