<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Crear el usuario maestro si no existe previamente por correo
        $user = User::firstOrCreate(
            ['email' => 'elplaka@hotmail.com'],
            [
                'name' => 'ANGEL',
                'last_name' => 'SANCHEZ DIAZ',
                'nickname' => 'asanchez',
                'celular' => '6941088943',
                'genero' => 'H',
                'es_activo' => 1,
                'password' => Hash::make('123'),
                'isAdmin' => 1,
                'user_type_id' => 1,
                'verificado' => 1
            ]
        );

        // 2. Asegurarse de que el rol ADMINISTRADOR exista y asignárselo
        $role = Role::firstOrCreate(['name' => 'ADMINISTRADOR']);
        $user->assignRole($role);

        // 3. Crear el permiso 'ver_usuarios' si no existe y dárselo al rol
        $permission = Permission::firstOrCreate(['name' => 'ver_usuarios']);
        $role->givePermissionTo($permission);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Opcional: Revertir los cambios si haces un migrate:rollback
        $user = User::where('email', 'elplaka@hotmail.com')->first();
        if ($user) {
            $user->removeRole('ADMINISTRADOR');
            $user->delete();
        }
    }
};
