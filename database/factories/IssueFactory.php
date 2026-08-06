<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Issue;
use App\Models\Project;
use App\Enums\IssueStatus;
use App\Enums\OccurrenceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
final class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => OccurrenceType::Exception,
            'fingerprint' => hash('sha1', fake()->unique()->uuid()),
            'title' => 'RuntimeException',
            'message' => fake()->sentence(),
            'file' => '/app/Http/Controllers/OrderController.php',
            'line' => fake()->numberBetween(1, 500),
            'occurrences_count' => 1,
            'status' => IssueStatus::Open,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ];
    }

    /**
     * Indicate that the issue has been resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => IssueStatus::Resolved,
        ]);
    }
}
