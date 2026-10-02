<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role; // Importa la clase Role

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define los roles que quieres insertar
        $roles = [
            'ADMINISTRADOR',
            'AUDITOR',
            'AUXILIAR',
            'DIRECTOR',
        ];

        // Recorre el array y crea cada rol
       foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role], ['guard_name' => 'web']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Si necesitas revertir, puedes eliminar los roles
        Role::whereIn('name', [
            'ADMINISTRADOR',
            'AUDITOR',
            'AUXILIAR',
            'DIRECTOR',
        ])->delete();
    }
};