<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Authenticate extends Middleware
{
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        // do it like in the parent
        $this->authenticate($request, $guards);

        // adds to parent: only user with the login or admin group pass
        $groups = \Auth::user()->getGroups();
        if ($groups->contains('login') || $groups->contains('admin')) {
            return $next($request);
        }
        // otherwise: non-pass
        throw new UnauthorizedHttpException('login-group', 'You are not authorized to access this page');
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
