<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run()
    {
        $permisos = [
            'ver_solicitudes',
            'ver_solicitudes_folio_digital',
            'crear_solicitudes',
            'editar_solicitudes',

            'ver_usuarios',
            'crear_usuarios',
            'editar_usuarios'
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::where('name', 'ADMINISTRADOR')->first();
        $auditor = Role::where('name', 'AUDITOR')->first();
        $auxiliar = Role::where('name', 'AUXILIAR')->first();
        $director = Role::where('name', 'DIRECTOR')->first();

        $admin->givePermissionTo($permisos);

        $auditor->givePermissionTo([
            'ver_solicitudes',
            'ver_solicitudes_folio_digital',
            'crear_solicitudes',
            'editar_solicitudes',
        ]);

        $auxiliar->givePermissionTo([
            'ver_solicitudes',
            'crear_solicitudes',
            'editar_solicitudes',
        ]);

        $director->givePermissionTo([
            'ver_solicitudes',
            'ver_solicitudes_folio_digital',
            'crear_solicitudes',
            'editar_solicitudes',
        ]);
    }
}
