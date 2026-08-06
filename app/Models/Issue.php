<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IssueStatus;
use Carbon\CarbonInterface;
use App\Enums\OccurrenceType;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property-read string $id
 * @property-read string $project_id
 * @property-read OccurrenceType $type
 * @property-read string $fingerprint
 * @property-read string $title
 * @property-read string|null $message
 * @property-read string|null $file
 * @property-read int|null $line
 * @property-read int $occurrences_count
 * @property-read IssueStatus $status
 * @property-read CarbonInterface $first_seen_at
 * @property-read CarbonInterface $last_seen_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Project $project
 * @property-read Collection<int, Occurrence> $occurrences
 */
final class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<Occurrence, $this>
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class);
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'project_id' => 'string',
            'type' => OccurrenceType::class,
            'fingerprint' => 'string',
            'title' => 'string',
            'message' => 'string',
            'file' => 'string',
            'line' => 'integer',
            'occurrences_count' => 'integer',
            'status' => IssueStatus::class,
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
