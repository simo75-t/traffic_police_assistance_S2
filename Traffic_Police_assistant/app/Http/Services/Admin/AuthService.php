<?php

namespace App\Http\Services\Admin;

use App\Enums\RoleUserEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * @param array{email: string, password: string} $credentials
     */
    public function attemptLogin(array $credentials): ?User
    {
        $authenticated = Auth::guard('web')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'role' => RoleUserEnum::Admin,
            'is_active' => 1,
        ]);

        if (! $authenticated) {
            return null;
        }

        /** @var User|null $user */
        $user = Auth::guard('web')->user();

        return $user;
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}

