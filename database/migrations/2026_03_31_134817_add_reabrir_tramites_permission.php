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
        // 1. Crear el permiso para reabrir trámites
        $permissionName = 'reabrir_tramites';
        $permission = Permission::firstOrCreate(['name' => $permissionName]);

        // 2. Definir los roles que tendrán este poder (Normalmente solo niveles altos)
        $roleNames = ['ADMINISTRADOR', 'DIRECTOR'];

        foreach ($roleNames as $roleName) {
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
        $permissionName = 'reabrir_tramites';
        $roleNames = ['ADMINISTRADOR', 'DIRECTOR'];

        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->revokePermissionTo($permissionName);
            }
        }

        // Eliminar el permiso de la tabla global
        Permission::where('name', $permissionName)->delete();
    }
};
