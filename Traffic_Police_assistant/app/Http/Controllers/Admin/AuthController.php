<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Services\Admin\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function showLoginForm(): View
    {
        return view("admin.login");
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $attrs = $request->validated();
        $user = $this->authService->attemptLogin($attrs);

        if (! $user) {
            return redirect()->back()
                ->withErrors(['login' => 'Email or password is incorrect.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->authService->logout($request);

        return redirect()->route('admin.login');
    }
}
