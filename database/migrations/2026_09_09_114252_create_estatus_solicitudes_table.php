<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estatus_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 255)->nullable();
            $table->string('color', 20)->nullable();
            $table->timestamps();
        });

        DB::table('estatus_solicitudes')->insert([
            [
                'nombre' => 'EN TRÁMITE',
                'descripcion' => 'La solicitud se encuentra registrada y en proceso de revisión.',
                'color' => 'warning',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'APROBADA',
                'descripcion' => 'La solicitud ha sido aceptada; pendiente de la entrega física o dispersión del recurso.',
                'color' => 'primary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'APOYO ENTREGADO',
                'descripcion' => 'El recurso o apoyo ha sido entregado exitosamente al beneficiario.',
                'color' => 'success',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'CANCELADA',
                'descripcion' => 'La solicitud ha sido descartada o dada de baja del sistema.',
                'color' => 'danger',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estatus_solicitudes');
    }
};
