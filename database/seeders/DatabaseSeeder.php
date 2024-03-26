<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LGASeeder::class,
            StateSeeder::class,
            RoleAndPermissionSeeder::class
        ]);
        User::firstOrCreate([
            'name'  => 'Super Admin',
            'email' => 'super-admin@domain.com',
            'password' => 'Password@123',
        ]);
    }
}
