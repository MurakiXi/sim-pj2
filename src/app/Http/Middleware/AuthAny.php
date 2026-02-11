<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

class AuthAny
{
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> feature/correction
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if (auth('admin')->check()) {
            return $next($request);
        }

        if (auth('web')->check()) {
            $user = auth('web')->user();

            if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                return $request->expectsJson()
                    ? abort(403, 'Your email address is not verified.')
                    : Redirect::guest(URL::route('verification.notice'));
            }

            return $next($request);
        }

        return $request->is('admin/*')
            ? redirect()->route('admin.login')
            : redirect()->route('login');
    }
}
