<?php

namespace App\Http\Middleware;

use Closure;

class UseFortifyAdminGuard
{
    public function handle($request, Closure $next)
    {
        config(['fortify.guard' => 'admin']);
        return $next($request);
    }
}
