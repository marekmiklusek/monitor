<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Enums\IssueStatus;
use App\Actions\ChangeIssueStatus;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\IssueStatusUpdateRequest;

final readonly class IssueStatusController
{
    public function __construct(private ChangeIssueStatus $changeIssueStatus)
    {
        // ...
    }

    public function __invoke(IssueStatusUpdateRequest $request, Issue $issue): RedirectResponse
    {
        $status = $request->status();

        $this->changeIssueStatus->execute($issue, $status);

        $message = match ($status) {
            IssueStatus::Open => __('Issue reopened.'),
            IssueStatus::Resolved => __('Issue resolved.'),
            IssueStatus::Ignored => __('Issue ignored.'),
        };

        if ($status === IssueStatus::Open) {
            return back()->with('flash', $message);
        }

        return to_route('issues.index')->with('flash', $message);
    }
}
