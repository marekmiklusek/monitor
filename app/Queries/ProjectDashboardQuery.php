<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Project;
use App\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Builder;

final readonly class ProjectDashboardQuery
{
    /**
     * @return Builder<Project>
     */
    public function builder(): Builder
    {
        return Project::query()
            ->withCount([
                'issues as open_issues_count' => fn (Builder $query): Builder => $query->where('status', IssueStatus::Open),
            ])
            ->orderBy('name');
    }
}
