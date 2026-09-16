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

        if ($user && $user->role === 'student' && $user->must_change_password) {
            if (!$request->routeIs('student.password.change', 'student.password.update', 'logout')) {
                return redirect()->route('student.password.change')
                    ->with('force_change', true);
            }
        }

        return $next($request);
    }
}
