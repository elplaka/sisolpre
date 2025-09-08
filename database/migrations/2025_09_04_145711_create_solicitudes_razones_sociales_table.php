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
        // Se crea la tabla 'solicitudes_razones_sociales' para guardar la información de la razón social
        // de una solicitud. Se le asocia la clave foránea 'id_solicitud' que tiene una relación uno a uno.
        Schema::create('solicitudes_razones_sociales', function (Blueprint $table) {
            $table->id();

            // La clave foránea que establece la relación uno a uno con la tabla 'solicitudes'.
            // El método unique() asegura que cada id de solicitud solo aparezca una vez en esta tabla.
            $table->foreignId('id_solicitud')
                  ->unique()
                  ->constrained('solicitudes')
                  ->onDelete('cascade');

            // Campos para la razón social
            $table->string('nombre', 255);

            // Marca de tiempo para controlar la creación y actualización del registro.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Se eliminan la tabla y la clave foránea en el orden inverso
        Schema::dropIfExists('solicitudes_razones_sociales');
    }
};
