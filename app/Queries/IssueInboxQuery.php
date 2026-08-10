<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Issue;
use App\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Builder;

final readonly class IssueInboxQuery
{
    /**
     * @return Builder<Issue>
     */
    public function builder(?IssueStatus $status, ?string $projectId): Builder
    {
        return Issue::query()
            ->with('project')
            ->when($status instanceof IssueStatus, fn (Builder $query): Builder => $query->where('status', $status))
            ->when($projectId !== null, fn (Builder $query): Builder => $query->where('project_id', $projectId))
            ->latest('last_seen_at');
    }
}
