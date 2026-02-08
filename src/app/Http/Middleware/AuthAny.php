<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthAny
{
<<<<<<< HEAD
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
=======
>>>>>>> feature/correction
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('web')->check() || Auth::guard('admin')->check()) {
            return $next($request);
        }

        if ($request->is('admin/*')) {
            return redirect()->route('admin.login');
        }

        return redirect()->route('login');
    }
}
