<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Support\TrafficSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = TrafficSeedData::users();
        $baseUsers = [$users['admin'], $users['manager'], ...$users['officers']];

        foreach ($baseUsers as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'password' => Hash::make('12345678'),
                    'role' => $user['role'],
                    'profile_image' => $user['profile_image'],
                    'is_active' => $user['is_active'],
                    'fcm_token' => Str::random(80),
                    'last_seen_at' => now()->subMinutes($user['last_seen_minutes_ago']),
                ]
            );
        }
    }
}
