<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Creación de la Estructura (Schema)
        Schema::create('requisitos_documentacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('nombre_corto', 50)->unique();
            $table->boolean('editable')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 2. Inserción de Datos Iniciales (Inline Seeding)
        $now = Carbon::now();

        DB::statement('ALTER TABLE requisitos_documentacion AUTO_INCREMENT = 2;');

        $requisitos = [
            // Requisitos Generales de Identificación y Solicitud
            [ 'nombre' => 'IDENTIFICACIÓN OFICIAL VIGENTE', 'nombre_corto' => 'ID_OFICIAL', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            
            // Requisitos de Propiedad y Predio
            [ 'nombre' => 'DOCUMENTO LEGAL QUE ACREDITE LA PROPIEDAD', 'nombre_corto' => 'DOC_PROPIEDAD', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'CROQUIS DE UBICACIÓN O PLANO TOPOGRÁFICO', 'nombre_corto' => 'CROQUIS_PLANO', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'PLANO CATASTRAL O LEVANTAMIENTO TOPOGRÁFICO', 'nombre_corto' => 'PLANO_CATASTRAL', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'CÉDULA CATASTRAL', 'nombre_corto' => 'CEDULA_CATASTRAL', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'COMPROBANTE DE PAGO DE PREDIAL ACTUALIZADO', 'nombre_corto' => 'PAGO_PREDIAL', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            
            // Requisitos de Trámite Específico
            [ 'nombre' => 'CONSTANCIA DE ZONIFICACIÓN ACTUALIZADA', 'nombre_corto' => 'CONST_ZONIFICACION', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'DICTAMEN DE COMPATIBILIDAD DE USO DE SUELO', 'nombre_corto' => 'DICTAMEN_USO_SUELO', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'PROYECTO ARQUITECTÓNICO PRELIMINAR', 'nombre_corto' => 'PROYECTO_PRELIMINAR', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'CONSTANCIA DE AUTORIZACIÓN O NO AFECTACIÓN DEL INAH', 'nombre_corto' => 'AUTORIZACION_INAH', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'COMPROBANTE DE PAGO DE DERECHOS', 'nombre_corto' => 'PAGO_DERECHOS', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'CONSTANCIA DE ALINEAMIENTO', 'nombre_corto' => 'CONST_ALINEAMIENTO', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'CONSTANCIA DE USO DE SUELO', 'nombre_corto' => 'CONST_USO_SUELO', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'CONSTANCIA DE NÚMERO OFICIAL', 'nombre_corto' => 'CONST_NUM_OFICIAL', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'PROYECTO ARQUITECTÓNICO COMPLETO', 'nombre_corto' => 'PROYECTO_COMPLETO', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'MEMORIA DESCRIPTIVA Y CÁLCULO ESTRUCTURAL', 'nombre_corto' => 'MEMORIA_ESTRUCTURAL', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'VISTO BUENO DE PROTECCIÓN CIVIL', 'nombre_corto' => 'VISTO_BUENO_PC', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'ESTUDIO DE IMPACTO AMBIENTAL O CONSTANCIA DE NO REQUERIRLO', 'nombre_corto' => 'ESTUDIO_IMPACTO_AMB', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'PROYECTO ARQUITECTÓNICO MODIFICADO', 'nombre_corto' => 'PROYECTO_MODIFICADO', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'MEMORIA DESCRIPTIVA TÉCNICA', 'nombre_corto' => 'MEMORIA_TECNICA', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'FOTOGRAFÍAS DEL ESTADO ACTUAL DEL INMUEBLE/PREDIO', 'nombre_corto' => 'FOTOS_ACTUALES', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'FOTOGRAFÍAS DEL PREDIO Y SU ENTORNO FRONTAL', 'nombre_corto' => 'FOTOS_FRENTE', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'DESCRIPCIÓN TÉCNICA', 'nombre_corto' => 'DESC_TECNICA', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'DICTAMEN ESTRUCTURAL', 'nombre_corto' => 'DICTAMEN_ESTRUCTURAL', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'PLANO O CROQUIS DESCRIPTIVO', 'nombre_corto' => 'PLANO_CROQUIS', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'CRONOGRAMA ESTIMADO DE EJECUCIÓN', 'nombre_corto' => 'CRONOGRAMA_EJECUCION', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'MEDIDAS DE SEGURIDAD', 'nombre_corto' => 'MEDIDAS_SEGURIDAD', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'JUSTIFICACIÓN TÉCNICA DE LA INTERVENCIÓN', 'nombre_corto' => 'JUSTIFICACION_TECNICA', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'MEDIDAS DE PROTECCIÓN Y SEÑALAMIENTO VIAL', 'nombre_corto' => 'MEDIDAS_PROTECCION', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'VISTO BUENO DE AGUA POTABLE/DRENAJE', 'nombre_corto' => 'VISTO_BUENO_AGUA', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'AUTORIZACIÓN DE TRÁNSITO', 'nombre_corto' => 'AUTORIZACION_TRANSITO', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'FOTOGRAFÍAS DEL ESTADO PREVIO DEL SITIO', 'nombre_corto' => 'FOTOS_PREVIAS', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'COMPROMISO DE REPOSICIÓN DE PAVIMENTO', 'nombre_corto' => 'COMPROMISO_REPOSICION', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'COMPROMISO DE EJECUCIÓN CONFORME A ACCESIBILIDAD', 'nombre_corto' => 'COMPROMISO_ACCESIBILIDAD', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'JUSTIFICACIÓN DEL USO TEMPORAL', 'nombre_corto' => 'JUSTIFICACION_USO_TEMPORAL', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'PROGRAMA DE LIMPIEZA O RESTITUCIÓN POSTERIOR', 'nombre_corto' => 'PROGRAMA_LIMPIEZA', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'AUTORIZACIÓN AMBIENTAL O DE RUIDO', 'nombre_corto' => 'AUTORIZACION_AMBIENTAL', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'SEGURO DE RESPONSABILIDAD CIVIL', 'nombre_corto' => 'SEGURO_RESP_CIVIL', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
            [ 'nombre' => 'PLANO ARQUITECTÓNICO DE LA EDIFICACIÓN EXISTENTE', 'nombre_corto' => 'PLANO_EXISTENTE', 'editable' => true, 'created_at' => $now, 'updated_at' => $now ],
        ];

        // Convertir todos los nombres a mayúsculas antes de la inserción para consistencia
        $requisitos = array_map(function($req) {
            $req['nombre'] = mb_strtoupper($req['nombre']);
            return $req;
        }, $requisitos);

        // Inserción masiva
        DB::table('requisitos_documentacion')->insert($requisitos);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitos_documentacion');
    }
};