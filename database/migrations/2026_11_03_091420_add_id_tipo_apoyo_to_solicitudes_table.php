<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->foreignId('id_tipo_apoyo')
                ->nullable()
                ->after('cantidad_aprobada')
                ->constrained('tipos_apoyo')
                ->restrictOnDelete();
        });

        // Clasificar los registros existentes.
        DB::table('solicitudes')
            ->whereNull('cantidad_aprobada')
            ->update(['id_tipo_apoyo' => 2]);

        DB::table('solicitudes')
            ->whereNotNull('cantidad_aprobada')
            ->update(['id_tipo_apoyo' => 1]);
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_apoyo']);
            $table->dropColumn('id_tipo_apoyo');
        });
    }
};
