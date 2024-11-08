<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        $rawPassword = "password";
        $hashedPassword = Hash::make($rawPassword);

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => $hashedPassword,
        ]);
    }
}
