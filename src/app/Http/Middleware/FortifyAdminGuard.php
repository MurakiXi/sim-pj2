<?php

namespace App\Http\Middleware;

use Closure;

class FortifyAdminGuard
{
    public function handle($request, Closure $next)
    {
        if (!$request->is('admin/*')) {
            return $next($request);
        }

        config(['fortify.guard' => 'admin']);
        return $next($request);
    }
}
