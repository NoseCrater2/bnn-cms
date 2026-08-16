<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        $route = match ($user->role) {
            UserRole::ADMIN => route('admin.dashboard'),
            UserRole::USER => route('home'),
        };

        return redirect()->to($route);
    }
}
