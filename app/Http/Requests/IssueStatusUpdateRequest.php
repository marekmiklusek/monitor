<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\IssueStatus;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class IssueStatusUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(IssueStatus::class)],
        ];
    }

    public function status(): IssueStatus
    {
        return IssueStatus::from($this->string('status')->toString());
    }
}
