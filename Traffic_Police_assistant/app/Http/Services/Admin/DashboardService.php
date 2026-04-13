<?php

namespace App\Http\Services\Admin;

use App\Models\User;

class DashboardService
{
    /**
     * @return array{totalUsers: int, activeUsers: int, inactiveUsers: int, latestUsers: \Illuminate\Database\Eloquent\Collection<int, User>}
     */
    public function getDashboardStats(): array
    {
        $totalUsers = User::query()->count();
        $activeUsers = User::query()->where('is_active', true)->count();
        $inactiveUsers = User::query()->where('is_active', false)->count();
        $latestUsers = User::query()->latest()->take(5)->get();

        return [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'latestUsers' => $latestUsers,
        ];
    }
}
