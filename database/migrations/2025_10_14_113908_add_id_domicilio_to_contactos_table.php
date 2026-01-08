<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega la columna id_domicilio a la tabla 'contactos'.
     */
    public function up(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            
            // 1. Definición de la Clave Foránea
            // Usamos after('email') para colocarla en la posición deseada.
            $table->foreignId('id_domicilio')
                  ->nullable() // Permite valores NULL
                  ->default(null) // El valor por defecto es NULL
                  ->constrained('domicilios_notificaciones') // Relación con la tabla 'domicilios_notificaciones'
                  ->onDelete('set null') // Si el domicilio de notificación se elimina, se establece a NULL.
                  ->after('email');
                  
            // Opcional: Agregar un índice simple para mejorar el rendimiento de búsqueda/join
            $table->index('id_domicilio'); 
        });
    }

    /**
     * Reverse the migrations.
     * Elimina la columna id_domicilio de la tabla 'contactos'.
     */
    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            // 1. Eliminar la clave foránea primero (Convención de Laravel)
            $table->dropConstrainedForeignId('id_domicilio');

            // 2. Eliminar la columna
            $table->dropColumn('id_domicilio');
        });
    }
};