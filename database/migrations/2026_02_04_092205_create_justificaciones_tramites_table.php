<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('justificaciones_tramites', function (Blueprint $table) {
            $table->id();

            // Relación con el trámite (Asegúrate que la tabla se llame 'tramites')
            $table->foreignId('id_tramite')
                  ->constrained('tramites')
                  ->onDelete('cascade');

            // El contenido de la justificación técnica o legal
            $table->text('motivo');

            $table->timestamps();           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('justificaciones_tramites');
    }
};
