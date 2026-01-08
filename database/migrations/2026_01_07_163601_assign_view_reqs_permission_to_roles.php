<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Crear el permiso (o buscarlo si ya existe)
        $permissionName = 'ver_requisitos';
        $permission = Permission::firstOrCreate(['name' => $permissionName]);

        // 2. Definir los roles
        $roleNames = ['ADMINISTRADOR', 'DIRECTOR', 'AUDITOR'];

        foreach ($roleNames as $roleName) {
            // Buscamos el rol
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                // Asignamos el permiso al rol
                $role->givePermissionTo($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionName = 'ver_requisitos';
        $roleNames = ['ADMINISTRADOR', 'DIRECTOR', 'AUDITOR'];

        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->revokePermissionTo($permissionName);
            }
        }

        // Opcional: Eliminar el permiso por completo de la tabla permissions
        Permission::where('name', $permissionName)->delete();
    }
};