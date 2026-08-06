<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\Project;
use App\Enums\OccurrenceType;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Middleware\AuthenticateProject;
use Illuminate\Contracts\Validation\ValidationRule;

final class IngestRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'schema_version' => ['required', 'integer', Rule::in([1])],
            'sent_at' => ['required', 'string', 'date'],
            'environment' => ['required', 'string', 'min:1', 'max:255'],
            'occurrences' => ['required', 'array', 'min:1', 'max:100'],
            'occurrences.*.type' => ['required', Rule::enum(OccurrenceType::class)],
            'occurrences.*.occurred_at' => ['required', 'string', 'date'],
            'occurrences.*.exception_class' => ['nullable', 'string', 'min:1', 'max:255'],
            'occurrences.*.message' => ['nullable', 'string', 'min:1', 'max:10000'],
            'occurrences.*.file' => ['nullable', 'string', 'min:1', 'max:1024'],
            'occurrences.*.line' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'occurrences.*.stack' => ['nullable', 'array', 'min:1', 'max:200'],
            'occurrences.*.context' => ['nullable', 'array', 'min:1', 'max:100'],
            'occurrences.*.channel' => ['nullable', 'string', 'min:1', 'max:255'],
            'occurrences.*.breadcrumbs' => ['nullable', 'array', 'min:1', 'max:100'],
            'occurrences.*.breadcrumbs.*.level' => ['required', 'string', 'min:1', 'max:32'],
            'occurrences.*.breadcrumbs.*.message' => ['required', 'string', 'min:1', 'max:10000'],
            'occurrences.*.breadcrumbs.*.context' => ['nullable', 'array', 'min:1', 'max:100'],
            'occurrences.*.breadcrumbs.*.logged_at' => ['required', 'string', 'date'],
        ];
    }

    public function project(): Project
    {
        /** @var Project $project */
        $project = $this->attributes->get(AuthenticateProject::ATTRIBUTE);

        return $project;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function occurrences(): array
    {
        /** @var array<int, array<string, mixed>> $occurrences */
        $occurrences = $this->validated('occurrences');

        return $occurrences;
    }
}
