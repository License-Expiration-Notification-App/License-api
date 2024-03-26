<?php

namespace Database\Seeders;

use App\Models\LocalGovernmentArea;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    protected $roles = [

        ['super-admin', 'Super Admin', 'Super Admin'],


    ];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = $this->roles;
        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role[0],
                'display_name' => $role[1],
                'description' => $role[2],
            ]);
        }
    }
}
