<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Issue;
use App\Models\Occurrence;
use App\Enums\OccurrenceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Occurrence>
 */
final class OccurrenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'payload' => [
                'type' => OccurrenceType::Exception->value,
                'exception_class' => 'RuntimeException',
                'message' => fake()->sentence(),
            ],
            'occurred_at' => now(),
        ];
    }
}
