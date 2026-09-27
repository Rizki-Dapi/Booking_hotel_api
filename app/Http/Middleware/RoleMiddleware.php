<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! collect($roles)->contains(fn (string $role) => $user->hasRole($role))) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You do not have access to this resource',
            ], 403);
        }

        return $next($request);
    }
}
