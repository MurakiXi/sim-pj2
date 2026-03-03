<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'テスト 太郎',   'email' => 'user1@example.com', 'password' => 'user1pass'],
            ['name' => '試験 次郎',     'email' => 'user2@example.com', 'password' => 'user2pass'],
            ['name' => 'サンプル 三郎', 'email' => 'user3@example.com', 'password' => 'user3pass'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name'              => $u['name'],
                    'password'          => Hash::make($u['password']),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
