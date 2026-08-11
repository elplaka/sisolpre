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
        Schema::create('constancias_numero_oficial', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_tramite')
                ->constrained('tramites')
                ->onDelete('restrict'); // No permite borrar una plantilla si tiene constancias

            $table->unsignedSmallInteger('ano_oficio');
            $table->string('prefijo_oficio', 25);
            $table->unsignedInteger('consecutivo_oficio');

            $table->unique(['ano_oficio', 'consecutivo_oficio'], 'num_oficio_unico');

            // Relación con la tabla de plantillas
            // El uso de constrained() asume que la tabla se llama 'plantillas_numero_oficial'
            $table->foreignId('id_plantilla')
                ->constrained('constancias_numero_oficial_plantillas')
                ->onDelete('restrict'); // No permite borrar una plantilla si tiene constancias

            $table->foreignId('id_propiedad')
                ->constrained('propiedades')
                ->onDelete('restrict');

            $table->date('fecha_emision');
            $table->date('fecha_expiracion');

            $table->string('numero_asignado', 10);
            $table->string('numero_asignado_letra', 60);

            $table->foreignId('id_usuario')
                ->constrained('users')
                ->onDelete('restrict');

            $table->foreignId('id_autoridad_firmante')
                ->constrained('autoridades_firmantes')
                ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('constancias_numero_oficial');
    }
};
