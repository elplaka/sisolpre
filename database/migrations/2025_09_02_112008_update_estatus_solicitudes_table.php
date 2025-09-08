<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up(): void
    {
        DB::table('estatus_solicitudes')
            ->where('id', 6)
            ->update(['nombre' => 'CANCELADA', 'updated_at' => now()]);

        DB::table('estatus_solicitudes')
            ->where('id', 99)
            ->update(['nombre' => 'ACEPTADA', 'updated_at' => now()]);
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        DB::table('estatus_solicitudes')
            ->where('id', 6)
            ->update(['nombre' => 'CANCELADA', 'updated_at' => now()]);

        DB::table('estatus_solicitudes')
            ->where('id', 99)
            ->update(['nombre' => 'ACEPTADA', 'updated_at' => now()]);
    }
};