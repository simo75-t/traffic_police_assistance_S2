<?php

namespace App\Http\Services\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * @param array{name: string, email: string, password: string, role: string, is_active: bool|int|string} $attrs
     */
    public function createUser(array $attrs): User
    {
        return User::create([
            'role' => $attrs['role'],
            'name' => $attrs['name'],
            'email' => $attrs['email'],
            'password' => Hash::make($attrs['password']),
            'is_active' => (bool) $attrs['is_active'],
        ]);
    }

    public function getUserById(User $user): User
    {
        return $user;
    }

    /**
     * @param array{name: string, email: string, is_active: bool|int|string} $data
     */
    public function updateUser(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => (bool) $data['is_active'],
        ]);

        return $user;
    }


    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }

    /**
     * @param array{is_active: bool|int|string} $data
     */
    public function updateStatusAccount(User $user, array $data): User
    {
        $user->update([
            'is_active' => (bool) $data['is_active'],
        ]);

        return $user;
    }

    public function toggleStatus(User $user): User
    {
        $user->is_active = ! $user->is_active;
        $user->save();

        return $user;
    }

    /**
     * @param array{status?: string|null, search?: string|null, order_by?: string|null, order_direction?: string|null, role?: string|null} $params
     */
    public function getUserList(array $params = []): LengthAwarePaginator
    {
        $query = User::query();

        if (isset($params['role'])) {
            $query->where('role', $params['role']);
        }

        if (isset($params['status']) && $params['status'] !== null && $params['status'] !== '') {
            $query->where('is_active', $params['status'] === 'active');
        }

        if (isset($params['search']) && $params['search'] !== null && $params['search'] !== '') {
            $query->where('name', 'like', "%{$params['search']}%");
        }

        $orderBy = $params['order_by'] ?? 'created_at';
        $direction = $params['order_direction'] ?? 'desc';

        return $query->orderBy($orderBy, $direction)->paginate(10);
    }
}
