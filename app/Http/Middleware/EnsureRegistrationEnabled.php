<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureRegistrationEnabled
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if ($route !== null && in_array($route->getName(), ['register', 'register.store'], true)) {
            abort_unless(config()->boolean('fortify.registration_enabled'), 404);
        }

        return $next($request);
    }
}
