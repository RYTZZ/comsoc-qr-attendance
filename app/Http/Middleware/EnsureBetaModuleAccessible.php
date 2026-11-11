<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBetaModuleAccessible
{
    /**
     * Handle an incoming request.
     * Public and normal members see the Coming Soon experience.
     * Super Administrators, or tests with 'bypass_coming_soon' / 'preview_mode' or app in testing env when requested can access actual modules.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        // 1. Super Administrators can always access for maintenance and testing
        // 2. Administrators with ?preview_mode=1 can preview
        // 3. Automated backend unit/feature tests targeting functional endpoints can pass header/session/query bypass
        $canBypass = ($user && $user->isSuperAdmin())
            || ($user && $user->isAdmin() && $request->has('preview_mode'))
            || ($request->header('X-Bypass-Coming-Soon') === 'true')
            || (session('bypass_beta_coming_soon') === true);

        if ($canBypass) {
            return $next($request);
        }

        // For JSON/API requests, return clean 403 / coming soon payload
        if ($request->expectsJson() || $request->is('*/api/*')) {
            return response()->json([
                'success' => false,
                'status' => 'coming_soon',
                'module' => $module,
                'message' => "COMSOC " . ucfirst($module) . " is currently compiling behind the scenes. Stay tuned!",
            ], 403);
        }

        // For web views, render the playful Coming Soon experience
        return response()->view("coming-soon.{$module}", [
            'module' => $module,
        ]);
    }
}
