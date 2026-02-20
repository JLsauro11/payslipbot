<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class HRUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'username' => 'HRAdmin',
            'email' => 'hr@company.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }
}
