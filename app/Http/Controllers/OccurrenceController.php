<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Occurrence;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class OccurrenceController
{
    public function show(Issue $issue, Occurrence $occurrence): JsonResponse
    {
        throw_if($occurrence->issue_id !== $issue->id, NotFoundHttpException::class);

        return response()->json([
            'id' => $occurrence->id,
            'occurred_at' => $occurrence->occurred_at->toIso8601String(),
            'payload' => $occurrence->payload,
        ]);
    }
}
