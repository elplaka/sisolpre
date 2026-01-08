<?php

// database/migrations/YYYY_MM_DD_HHMMSS_create_estatus_tramites_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importar la fachada DB

return new class extends Migration
{
    /**
     * Ejecuta las migraciones (crea la tabla).
     */
    public function up(): void
    {
        // 1. Crea la tabla 'estatus_tramites' para catalogar los estados de un proceso.
        Schema::create('estatus_tramites', function (Blueprint $table) {
            
            // Columna ID (Llave Primaria, Autoincrementable)
            $table->unsignedBigInteger('id')->primary();

            // Columna Nombre: Almacena el nombre del estatus (ej: 'En Revisión', 'Aprobado', 'Rechazado')
            $table->string('nombre', 100)->unique();
            
            // Columna Activo: Indica si el estatus está disponible para ser usado.
            // Por defecto es TRUE (activo). Se mapea a TINYINT(1) en MySQL.
            $table->boolean('activo')->default(true); 
            $table->string('color', 30)->default('gray');

            // Columnas de Timestamps (created_at y updated_at)
            $table->timestamps();
        });

        $now = now();
        DB::table('estatus_tramites')->insert([
            // ID 1: EN REVISIÓN (como los anteriores, pero ahora con color)
            ['id' => 1, 'nombre' => 'EN REVISIÓN', 'activo' => true, 'color' => 'violet', 'created_at' => $now, 'updated_at' => $now],
            
            // ID 2: APROBADO (ahora con color)
            ['id' => 2, 'nombre' => 'APROBADO', 'activo' => true, 'color' => 'darkgreen', 'created_at' => $now, 'updated_at' => $now],
            
            // ID 3: SUSPENDIDO (ahora con color)
            ['id' => 3, 'nombre' => 'SUSPENDIDO', 'activo' => true, 'color' => 'orange', 'created_at' => $now, 'updated_at' => $now],
            
            // ID 4: RECHAZADO (ahora con color)
            ['id' => 4, 'nombre' => 'RECHAZADO', 'activo' => true, 'color' => 'tomato', 'created_at' => $now, 'updated_at' => $now],
            
            // ID 5: CANCELADO (activo = false y con color)
            ['id' => 5, 'nombre' => 'CANCELADO', 'activo' => false, 'color' => 'darkred', 'created_at' => $now, 'updated_at' => $now],
            
            // ID 99: ENTREGADO (ID especial y con color)
            ['id' => 99, 'nombre' => 'ENTREGADO', 'activo' => true, 'color' => 'mediumseagreen', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Revierte las migraciones (elimina la tabla).
     */
    public function down(): void
    {
        Schema::dropIfExists('estatus_tramites');
    }
};
