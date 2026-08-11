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
        $permissionName = 'ver_propiedades';

        $permission = Permission::firstOrCreate([
            'name' => $permissionName
        ]);

        // 2. Buscar únicamente el rol DIRECTOR
        $role = Role::where('name', 'ADMINISTRADOR')->first();

        // 3. Asignar el permiso al DIRECTOR
        if ($role) {
            $role->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionName = 'ver_propiedades';

        // 1. Revocar el permiso al DIRECTOR
        $role = Role::where('name', 'ADMINISTRADOR')->first();

        if ($role) {
            $role->revokePermissionTo($permissionName);
        }

        // 2. Eliminar el permiso
        Permission::where('name', $permissionName)->delete();
    }
};
