<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $groupName): Response
    {
        if (\Auth::user()?->getGroups()->contains($groupName)) {
            return $next($request);
        }
        // dump($groupName);
        // dump(\Auth::user()->getGroups());
        abort(403);
    }
}
