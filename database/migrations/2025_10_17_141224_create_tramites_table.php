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
            // Crea la tabla 'tramites'
            Schema::create('tramites', function (Blueprint $table) {

                  // ID: Llave primaria auto-incremental (Laravel standard)
                  $table->id();

                  // Llave Foránea a Solicitudes: Reemplaza el campo 'folio'
                  // Asume que la tabla 'solicitudes' usa 'id' como llave primaria (UNSIGNED BIGINT)
                  $table->foreignId('id_solicitud')
                        ->constrained('solicitudes') // Hace referencia a la tabla 'solicitudes' (usando su 'id')
                        ->onDelete('restrict');

                  // FK a catalogo_tramites: Define el tipo de trámite
                  $table->foreignId('id_tramite')
                        ->constrained('catalogo_tramites')
                        ->onDelete('restrict');

                  // Campos de Fechas
                  $table->date('fecha_inicio');
                  $table->date('fecha_fin')->nullable();

                  // FK a contactos
                  $table->foreignId('id_contacto')
                        ->constrained('contactos')
                        ->onDelete('restrict');

                  // FK a propiedades
                  $table->foreignId('id_propiedad')
                        ->constrained('propiedades')
                        ->onDelete('restrict');

                  // FK a estatus_tramites
                  $table->foreignId('id_estatus')
                        ->constrained('estatus_tramites')
                        ->onDelete('restrict');

                  // Timestamps de Laravel
                  $table->timestamps();

                  // Índices adicionales (Recomendado)
                  $table->index('fecha_inicio');
            });
      }

      /**
       * Reverse the migrations.
       */
      public function down(): void
      {
            Schema::dropIfExists('tramites');
      }
};
