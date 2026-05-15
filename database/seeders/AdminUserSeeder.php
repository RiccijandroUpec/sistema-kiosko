<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@kiosko.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'pin' => '1234',
            ]
        );
    }
}
