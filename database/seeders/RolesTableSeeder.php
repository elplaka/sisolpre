<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        Role::firstOrCreate(['name' => 'ADMINISTRADOR']);
        Role::firstOrCreate(['name' => 'DIRECTOR']);
        Role::firstOrCreate(['name' => 'AUDITOR']);
        Role::firstOrCreate(['name' => 'AUXILIAR']);
    }
}

