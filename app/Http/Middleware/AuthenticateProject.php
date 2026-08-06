<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use App\Models\Project;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateProject
{
    public const string ATTRIBUTE = 'monitoring_project';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return $this->unauthenticated();
        }

        $project = Project::query()
            ->where('token_hash', Project::hashToken($token))
            ->first();

        if ($project === null) {
            return $this->unauthenticated();
        }

        $request->attributes->set(self::ATTRIBUTE, $project);

        return $next($request);
    }

    private function unauthenticated(): Response
    {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
