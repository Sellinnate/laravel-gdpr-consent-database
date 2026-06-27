<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

/**
 * @extends Factory<ConsentType>
 */
class ConsentTypeFactory extends Factory
{
    /** @var class-string<ConsentType> */
    protected $model = ConsentType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->paragraph(),
            'required' => $this->faker->boolean(20),
            'active' => $this->faker->boolean(80),
            'category' => $this->faker->randomElement(['cookie', 'other']),
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the consent type is required.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes): array => ['required' => true]);
    }

    /**
     * Indicate that the consent type is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => ['active' => true]);
    }

    /**
     * Indicate that the consent type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['active' => false]);
    }

    /**
     * Indicate that the consent type belongs to the cookie category.
     */
    public function cookie(): static
    {
        return $this->state(fn (array $attributes): array => ['category' => 'cookie']);
    }

    /**
     * Indicate that the consent type belongs to the non-cookie category.
     */
    public function other(): static
    {
        return $this->state(fn (array $attributes): array => ['category' => 'other']);
    }
}
