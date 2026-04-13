<?php

namespace App\Http\Middleware;

use App\Enums\RoleUserEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsPoliceManager
{
    /**
     * Restrict the police manager area to active users with the exact manager role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user || $user->role !== RoleUserEnum::Police_manager || ! $user->is_active) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('policemanager.login')
                ->withErrors(['login' => 'You do not have permission to access the police manager area.']);
        }

        return $next($request);
    }
}
