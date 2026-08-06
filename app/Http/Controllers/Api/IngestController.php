<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\Response;
use App\Http\Requests\Api\IngestRequest;
use App\Jobs\ProcessIngestedOccurrences;

final class IngestController
{
    public function __invoke(IngestRequest $request): Response
    {
        dispatch(new ProcessIngestedOccurrences($request->project()->id, $request->occurrences()));

        return response()->noContent(202);
    }
}
