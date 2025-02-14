<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuthController
{
    public function __construct(private readonly AuthService $authService) {}

    public function login()
    {
        return $this->authService->prepareLogin();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (Auth::guest() && ! \App::runningUnitTests()) {
            [$identifiers, $userAttributes] = $this->authService->userFromCallback($request);

            $user = User::updateOrCreate($identifiers, $userAttributes);

            Auth::login($user);
        }

        return redirect()->intended(AppServiceProvider::HOME);
    }

    public function logout()
    {
        Auth::logout();

        // call after logout routine
        return $this->authService->afterLogout();
    }
}
