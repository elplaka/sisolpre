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
        Schema::create('tramites_documentacion', function (Blueprint $table) {
            $table->foreignId('id_tramite')
                ->constrained('tramites') // Hace referencia a la tabla 'solicitudes' (usando su 'id')
                ->onDelete('restrict');

            $table->foreignId('id_requisito_documentacion')
                ->constrained('requisitos_documentacion') // Hace referencia a la tabla 'requisitos_documentacion' (usando su 'id')
                ->onDelete('restrict');

            $table->boolean('entregado')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tramites_documentacion');
    }
};
