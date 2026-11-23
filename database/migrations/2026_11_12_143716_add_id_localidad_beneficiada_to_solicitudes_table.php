
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
            $table->foreignId('id_localidad_beneficiada')
                ->default(1)
                ->after('id_tipo_apoyo')
                ->constrained('localidades');
        });

        // Copiar la localidad actual del solicitante
        // a la localidad beneficiada de cada solicitud existente.
        DB::table('solicitudes')
            ->join(
                'solicitantes',
                'solicitudes.id_solicitante',
                '=',
                'solicitantes.id'
            )
            ->update([
                'solicitudes.id_localidad_beneficiada' =>
                DB::raw('solicitantes.id_localidad')
            ]);
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_localidad_beneficiada');
        });
    }
};
