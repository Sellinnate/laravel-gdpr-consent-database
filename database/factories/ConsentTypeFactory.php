<?php

namespace Selli\LaravelGdprConsentDatabase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

class ConsentTypeFactory extends Factory
{
    protected $model = ConsentType::class;

    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->paragraph(),
            'required' => $this->faker->boolean(20), // 20% di probabilità che sia richiesto
            'active' => $this->faker->boolean(80), // 80% di probabilità che sia attivo
            'category' => $this->faker->randomElement(['cookie', 'other']),
            'metadata' => null,
        ];
    }

    /**
     * Indica che il tipo di consenso è richiesto.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function required()
    {
        return $this->state(function (array $attributes) {
            return [
                'required' => true,
            ];
        });
    }

    /**
     * Indica che il tipo di consenso è attivo.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'active' => true,
            ];
        });
    }

    /**
     * Indica che il tipo di consenso è inattivo.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'active' => false,
            ];
        });
    }

    /**
     * Indica che il tipo di consenso è relativo ai cookie.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function cookie()
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'cookie',
            ];
        });
    }

    /**
     * Indica che il tipo di consenso non è relativo ai cookie.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function other()
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'other',
            ];
        });
    }
}
