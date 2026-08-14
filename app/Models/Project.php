<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use App\Enums\HeartbeatStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read string $environment
 * @property-read string $token_hash
 * @property-read CarbonInterface|null $last_heartbeat_at
 * @property-read CarbonInterface|null $heartbeat_alerted_at
 * @property-read array<int, array{issue_id: string, kind: string}>|null $pending_issue_notifications
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read int $open_issues_count
 * @property-read int $recent_occurrences_count
 * @property-read Collection<int, Issue> $issues
 * @property-read Collection<int, Occurrence> $occurrences
 */
#[Hidden([
    'token_hash',
])]
final class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array{plain: string, hash: string}
     */
    public static function generateToken(): array
    {
        $plain = Str::random(64);

        return [
            'plain' => $plain,
            'hash' => self::hashToken($plain),
        ];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function heartbeatThreshold(): CarbonInterface
    {
        return now()->subMinutes(config()->integer('monitoring.heartbeat_threshold_minutes'));
    }

    /**
     * @return HasMany<Issue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * @return HasManyThrough<Occurrence, Issue, $this>
     */
    public function occurrences(): HasManyThrough
    {
        return $this->hasManyThrough(Occurrence::class, Issue::class);
    }

    public function heartbeatStatus(): HeartbeatStatus
    {
        if ($this->last_heartbeat_at === null) {
            return HeartbeatStatus::Missing;
        }

        return $this->last_heartbeat_at->lt(self::heartbeatThreshold())
            ? HeartbeatStatus::Stale
            : HeartbeatStatus::Ok;
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'environment' => 'string',
            'token_hash' => 'string',
            'last_heartbeat_at' => 'datetime',
            'heartbeat_alerted_at' => 'datetime',
            'pending_issue_notifications' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
