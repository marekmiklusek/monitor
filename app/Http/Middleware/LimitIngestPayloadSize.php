<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class LimitIngestPayloadSize
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maximum = config()->integer('monitoring.max_payload_kilobytes') * 1024;

        if ($this->size($request) > $maximum) {
            return response()->json(['message' => 'Payload too large.'], 413);
        }

        return $next($request);
    }

    private function size(Request $request): int
    {
        $header = $request->header('Content-Length');

        if (is_string($header) && ctype_digit($header)) {
            return (int) $header;
        }

        return mb_strlen($request->getContent(), '8bit');
    }
}
