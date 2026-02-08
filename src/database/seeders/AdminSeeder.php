<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $pass  = env('ADMIN_PASSWORD', 'adminpass');
        $name  = env('ADMIN_NAME', '管理者');

        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make($pass),
            ]
        );
    }
}
