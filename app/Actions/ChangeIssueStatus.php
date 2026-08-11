<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Issue;
use App\Enums\IssueStatus;

final readonly class ChangeIssueStatus
{
    public function execute(Issue $issue, IssueStatus $status): Issue
    {
        $issue->fill(['status' => $status])->save();

        return $issue;
    }
}
