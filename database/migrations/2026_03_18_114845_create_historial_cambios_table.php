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
        Schema::create('historial_cambios', function (Blueprint $table) {
            $table->id();

            // Relación polimórfica (crea auditable_id y auditable_type)
            $table->morphs('auditable');

            // Quién hizo el movimiento
            $table->foreignId('id_usuario')->constrained('users');

            // Opcional pero recomendado: Vincular siempre al trámite padre
            $table->foreignId('id_tramite_principal')->nullable()->constrained('tramites')->onDelete('cascade');

            $table->string('accion'); // 'CREAR', 'ACTUALIZAR', 'BORRAR'

            // Almacenamos el "antes" y "después"
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_cambios');
    }
};
