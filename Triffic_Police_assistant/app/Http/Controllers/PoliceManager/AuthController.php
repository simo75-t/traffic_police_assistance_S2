<?php

namespace App\Http\Controllers\PoliceManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\PoliceManager\LoginRequest;
use App\Http\Services\PoliceManager\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * Render the police manager login screen.
     */
    public function showLoginForm(): View
    {
        return view('policemanager.auth.login');
    }

    /**
     * Authenticate only active police managers and redirect them to their dashboard.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $user = $this->authService->attemptLogin($credentials);

        if (! $user) {
            return back()
                ->withErrors(['login' => 'Email or password is incorrect, or the account is inactive.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('policemanager.home'));
    }

    /**
     * End the authenticated session cleanly and send the user back to login.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->authService->logout($request);

        return redirect()->route('policemanager.login');
    }
}
