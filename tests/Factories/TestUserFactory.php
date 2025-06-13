<?php

namespace Selli\LaravelGdprConsentDatabase\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

class TestUserFactory extends Factory
{
    protected $model = TestUser::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
