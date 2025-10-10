<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define el nombre del permiso
        $permissionName = 'ver_estadisticas';

        // Busca el permiso en la base de datos para asegurarnos de que existe
        $permission = Permission::where('name', $permissionName)->first();

        // Si el permiso existe, procedemos a asignarlo
        if ($permission) {
            // Nombres de los roles a los que queremos asignar el permiso
            $roleNames = ['ADMINISTRADOR', 'DIRECTOR', 'AUDITOR'];

            // Recorre cada nombre de rol
            foreach ($roleNames as $roleName) {
                // Busca el rol por su nombre
                $role = Role::where('name', $roleName)->first();

                // Si el rol existe, le asignamos el permiso
                if ($role) {
                    $role->givePermissionTo($permission);
                    echo "Permiso '{$permissionName}' asignado al rol '{$roleName}'.\n";
                } else {
                    echo "Rol '{$roleName}' no encontrado. No se pudo asignar el permiso.\n";
                }
            }
        } else {
            echo "Permiso '{$permissionName}' no encontrado. No se pudo asignar a los roles.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Define el nombre del permiso
        $permissionName = 'ver_estadisticas';

        // Busca el permiso en la base de datos
        $permission = Permission::where('name', $permissionName)->first();

        // Si el permiso existe, procedemos a revocarlo
        if ($permission) {
            // Nombres de los roles a los que queremos revocar el permiso
            $roleNames = ['ADMINISTRADOR', 'DIRECTOR', 'AUDITOR'];

            // Recorre cada nombre de rol
            foreach ($roleNames as $roleName) {
                // Busca el rol por su nombre
                $role = Role::where('name', $roleName)->first();

                // Si el rol existe, le revocamos el permiso
                if ($role) {
                    $role->revokePermissionTo($permission);
                    echo "Permiso '{$permissionName}' revocado del rol '{$roleName}'.\n";
                }
            }
        }
    }
};