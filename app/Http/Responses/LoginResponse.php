<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request)
    {
        $user = $request->user();
        $routeName = $user?->getDashboardRoute() ?? 'dashboard';

        if ($user?->isParent() || $user?->isStudent()) {
            return redirect()->route($routeName);
        }

        return redirect()->intended(route($routeName));
    }
}
