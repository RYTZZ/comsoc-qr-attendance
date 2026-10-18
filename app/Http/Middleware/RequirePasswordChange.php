<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            // Allow password update routes, profile page, and logout
            if (!$request->routeIs('password.update', 'profile.edit', 'logout')) {
                return redirect()->route('profile.edit')
                    ->with('error', 'Security Policy: You are required to update your temporary or reset password before continuing.');
            }
        }

        return $next($request);
    }
}
