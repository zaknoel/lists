<?php

namespace Zak\Lists\Tests\Fixtures\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

/**
 * @extends Factory<TestUser>
 */
class TestUserFactory extends Factory
{
    protected $model = TestUser::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
