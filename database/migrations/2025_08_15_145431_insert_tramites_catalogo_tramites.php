<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importa la fachada DB
use Carbon\Carbon; // Para las marcas de tiempo

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define los registros a insertar
        $tramites = [
            [
                'id_tipo' => 1,
                'nombre' => 'SUBDIVISIÓN DE PREDIO RURAL',
                'nombre_abreviado' => 'SUBDIV. P. R.',
                'activo' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_tipo' => 1,
                'nombre' => 'SUBDIVISIÓN DE PREDIO URBANO',
                'nombre_abreviado' => 'SUBDIV. P. U.',
                'activo' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_tipo' => 1,
                'nombre' => 'FUSIÓN DE PREDIOS URBANOS',
                'nombre_abreviado' => 'FUSIÓN P. U.',
                'activo' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_tipo' => 1,
                'nombre' => 'CONSTANCIA DE UBICACIÓN DE PREDIO',
                'nombre_abreviado' => 'CONST. UBIC.',
                'activo' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        // Inserta los registros en la tabla 'catalogo_tramites'
        DB::table('catalogo_tramites')->insert($tramites);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Define los nombres de los trámites que se insertaron en la migración 'up'
        $tramiteNames = [
            'SUBDIVISIÓN DE PREDIO RURAL',
            'SUBDIVISIÓN DE PREDIO URBANO',
            'FUSIÓN DE PREDIOS URBANOS',
            'CONSTANCIA DE UBICACIÓN DE PREDIO',
        ];

        // Elimina los registros insertados
        DB::table('catalogo_tramites')->whereIn('nombre', $tramiteNames)->delete();
    }
};