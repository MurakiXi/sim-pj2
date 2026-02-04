<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->intended(route('admin.attendances.index'));
        }

        return redirect()->intended(config('fortify.home'));
    }
}
