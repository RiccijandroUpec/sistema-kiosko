<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kiosko.com'],
            [
                'name' => 'kioskoadmin',
                'password' => Hash::make('12345678'),
                'pin' => '3008',
                'role' => 'admin',
            ]
        );
    }
}
