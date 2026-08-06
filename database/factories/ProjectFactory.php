<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
final class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'environment' => 'production',
            'token_hash' => Project::generateToken()['hash'],
            'last_heartbeat_at' => null,
        ];
    }

    /**
     * Indicate that the project authenticates with the given plain token.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes): array => [
            'token_hash' => Project::hashToken($token),
        ]);
    }
}
