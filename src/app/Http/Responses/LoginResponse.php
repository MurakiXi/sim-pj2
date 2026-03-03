<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $intended = $request->session()->get('url.intended');
        $path = $intended ? (parse_url($intended, PHP_URL_PATH) ?? '') : '';

        if (Auth::guard('admin')->check()) {
            if ($path && !str_starts_with($path, '/admin')) {
                $request->session()->forget('url.intended');
            }
            return redirect()->intended(route('admin.attendances.index'));
        }

        if ($path && str_starts_with($path, '/admin')) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
