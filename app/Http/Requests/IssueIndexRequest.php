<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use App\Enums\IssueStatus;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class IssueIndexRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in([...array_column(IssueStatus::cases(), 'value'), 'all'])],
            'project' => ['nullable', 'string', Rule::exists(Project::class, 'id')],
        ];
    }

    public function status(): ?IssueStatus
    {
        $status = $this->query('status');

        if ($status === null) {
            return IssueStatus::Open;
        }

        if ($status === 'all') {
            return null;
        }

        return IssueStatus::tryFrom(is_string($status) ? $status : '');
    }

    public function projectId(): ?string
    {
        $project = $this->query('project');

        return is_string($project) && $project !== '' ? $project : null;
    }
}
