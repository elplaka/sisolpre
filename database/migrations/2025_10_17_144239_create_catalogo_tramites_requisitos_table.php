<?php

// database/migrations/YYYY_MM_DD_HHMMSS_create_catalogo_tramites_requisitos_table.php

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
                 // 1. Creación de la Estructura de la Tabla Pivote
            Schema::create('catalogo_tramites_requisitos', function (Blueprint $table) {
                
                // Llave Foránea 1: Referencia al Trámite del Catálogo
                $table->foreignId('id_tramite_catalogo') // Nombre solicitado
                    ->constrained('catalogo_tramites') 
                    ->onDelete('cascade');

                // Llave Foránea 2: Referencia al Requisito de Documentación
                $table->foreignId('id_requisito') // Nombre solicitado
                    ->constrained('requisitos_documentacion') 
                    ->onDelete('cascade');
                
                // Clave primaria compuesta para asegurar unicidad
                $table->primary(['id_tramite_catalogo', 'id_requisito'], 'tramite_requisito_pk');

                $table->boolean('obligatorio')->default(true);
                $table->boolean('activo')->default(true);
                $table->boolean('editable')->default(true);

                // Timestamps
                $table->timestamps();
            });

            DB::beginTransaction();

        try {


            // 2. Inserción de las Relaciones Requisito-Trámite (Mapping)
            $now = Carbon::now();

            // Función auxiliar para obtener el ID de un requisito por su nombre_corto
            $getRequisitoId = function ($nombreCorto) {
                return DB::table('requisitos_documentacion')->where('nombre_corto', $nombreCorto)->value('id');
            };
            
            $requisitosData = [];

            // Función auxiliar para obtener el ID de un requisito por su nombre_corto
            $getRequisitoId = function ($nombreCorto) {
                return DB::table('requisitos_documentacion')->where('nombre_corto', $nombreCorto)->value('id');
            };

            // Función auxiliar para obtener el ID de un trámite por su nombre (AJUSTA EL CAMPO DE BÚSQUEDA SI ES NECESARIO)
            // **IMPORTANTE**: Asume que tienes un campo 'nombre' en 'catalogo_tramites'.
            $getTramiteId = function ($nombreTramite) {
                
                return DB::table('catalogo_tramites')->where('nombre', $nombreTramite)->value('id');
            };

            $requisitosData = [];
            $OBLIGATORIO = false;
            
        // 1. CONSTANCIA DE ZONIFICACIÓN
            $id_zonificacion = $getTramiteId('CONSTANCIA DE ZONIFICACIÓN');
            if ($id_zonificacion) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_zonificacion, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_zonificacion, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_zonificacion, 'id_requisito' => $getRequisitoId('DOC_PROPIEDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_zonificacion, 'id_requisito' => $getRequisitoId('CROQUIS_PLANO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_zonificacion, 'id_requisito' => $getRequisitoId('PLANO_CATASTRAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_zonificacion, 'id_requisito' => $getRequisitoId('CEDULA_CATASTRAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_zonificacion, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_zonificacion, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }

            // 2. CONSTANCIA DE USO DE SUELO
            $id_uso_suelo = $getTramiteId('CONSTANCIA DE USO DE SUELO');
            if ($id_uso_suelo) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_uso_suelo, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_uso_suelo, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_uso_suelo, 'id_requisito' => $getRequisitoId('DOC_PROPIEDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_uso_suelo, 'id_requisito' => $getRequisitoId('DICTAMEN_USO_SUELO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_uso_suelo, 'id_requisito' => $getRequisitoId('CONST_ZONIFICACION'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_uso_suelo, 'id_requisito' => $getRequisitoId('CROQUIS_PLANO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_uso_suelo, 'id_requisito' => $getRequisitoId('PROYECTO_PRELIMINAR'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_uso_suelo, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_uso_suelo, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }

            // 3. CONSTANCIA DE ALINEAMIENTO
            $id_alineamiento = $getTramiteId('CONSTANCIA DE ALINEAMIENTO');
            if ($id_alineamiento) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_alineamiento, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_alineamiento, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_alineamiento, 'id_requisito' => $getRequisitoId('DOC_PROPIEDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_alineamiento, 'id_requisito' => $getRequisitoId('CROQUIS_PLANO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_alineamiento, 'id_requisito' => $getRequisitoId('FOTOS_FRENTE'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_alineamiento, 'id_requisito' => $getRequisitoId('CEDULA_CATASTRAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_alineamiento, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_alineamiento, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_alineamiento, 'id_requisito' => $getRequisitoId('AUTORIZACION_INAH'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
            }

            // 4. CONSTANCIA DE NÚMERO OFICIAL
            $id_num_oficial = $getTramiteId('CONSTANCIA DE NÚMERO OFICIAL');
            if ($id_num_oficial) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_num_oficial, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_num_oficial, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_num_oficial, 'id_requisito' => $getRequisitoId('DOC_PROPIEDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_num_oficial, 'id_requisito' => $getRequisitoId('CROQUIS_PLANO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_num_oficial, 'id_requisito' => $getRequisitoId('CONST_ALINEAMIENTO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_num_oficial, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_num_oficial, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }
            
            // 5. OBRA NUEVA (Licencia de Construcción - Obra Nueva)
            $id_obra_nueva = $getTramiteId('OBRA NUEVA');
            if ($id_obra_nueva) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('DOC_PROPIEDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('CONST_ZONIFICACION'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('CONST_USO_SUELO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('CONST_ALINEAMIENTO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('CONST_NUM_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('PROYECTO_COMPLETO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('MEMORIA_ESTRUCTURAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('VISTO_BUENO_PC'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('AUTORIZACION_INAH'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('ESTUDIO_IMPACTO_AMB'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_obra_nueva, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }
            
            // 6. AMPLIACIÓN DE OBRA (Licencia de Remodelacion o Ampliacion)
            $id_ampliacion = $getTramiteId('AMPLIACIÓN DE OBRA');
            if ($id_ampliacion) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('DOC_PROPIEDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('CONST_ZONIFICACION'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('CONST_USO_SUELO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('CONST_ALINEAMIENTO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('CONST_NUM_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('PROYECTO_MODIFICADO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('MEMORIA_TECNICA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('FOTOS_ACTUALES'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('VISTO_BUENO_PC'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('AUTORIZACION_INAH'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ampliacion, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }

            // 7. CAMBIO DE USO (Permiso cambio de Uso del Suelo)
            $id_cambio_uso = $getTramiteId('CAMBIO DE USO');
            if ($id_cambio_uso) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('DOC_PROPIEDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('CONST_ZONIFICACION'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('CONST_USO_SUELO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('PLANO_EXISTENTE'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('FOTOS_ACTUALES'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('DICTAMEN_ESTRUCTURAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('VISTO_BUENO_PC'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('ESTUDIO_IMPACTO_AMB'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_cambio_uso, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }

            // 8. REPARACIÓN O REMODELACIÓN
            $id_reparacion = $getTramiteId('REPARACIÓN O REMODELACIÓN');
            if ($id_reparacion) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('DOC_PROPIEDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('CONST_ZONIFICACION'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('CONST_USO_SUELO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('CONST_ALINEAMIENTO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('CONST_NUM_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('PROYECTO_MODIFICADO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('MEMORIA_TECNICA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('FOTOS_ACTUALES'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('VISTO_BUENO_PC'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('AUTORIZACION_INAH'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_reparacion, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }

            // 9. DEMOLICIÓN
            $id_demolicion = $getTramiteId('DEMOLICIÓN');
            if ($id_demolicion) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('DOC_PROPIEDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('DESC_TECNICA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('DICTAMEN_ESTRUCTURAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('FOTOS_ACTUALES'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('AUTORIZACION_INAH'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('VISTO_BUENO_PC'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('CRONOGRAMA_EJECUCION'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('MEDIDAS_SEGURIDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('PLANO_CROQUIS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_demolicion, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }

            // 10. BARDAS O CERCAS (Permiso para Construcción de Bardas y Cercas)
            $id_bardas = $getTramiteId('BARDAS O CERCAS');
            if ($id_bardas) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_bardas, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_bardas, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_bardas, 'id_requisito' => $getRequisitoId('DOC_PROPIEDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_bardas, 'id_requisito' => $getRequisitoId('CONST_ALINEAMIENTO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_bardas, 'id_requisito' => $getRequisitoId('CONST_NUM_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_bardas, 'id_requisito' => $getRequisitoId('PLANO_CROQUIS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_bardas, 'id_requisito' => $getRequisitoId('FOTOS_ACTUALES'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_bardas, 'id_requisito' => $getRequisitoId('AUTORIZACION_INAH'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_bardas, 'id_requisito' => $getRequisitoId('VISTO_BUENO_PC'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_bardas, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_bardas, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }

            // 11. ROTURA O CORTE DE PAVIMENTO
            $id_rotura = $getTramiteId('ROTURA O CORTE DE PAVIMENTO');
            if ($id_rotura) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('JUSTIFICACION_TECNICA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('CROQUIS_PLANO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('CRONOGRAMA_EJECUCION'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('MEDIDAS_PROTECCION'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('VISTO_BUENO_AGUA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('AUTORIZACION_TRANSITO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('FOTOS_PREVIAS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('COMPROMISO_REPOSICION'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rotura, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }
            
            // 12. EXCAVACIÓN O RELLENO
            $id_excavacion = $getTramiteId('EXCAVACIÓN O RELLENO');
            if ($id_excavacion) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_excavacion, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_excavacion, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_excavacion, 'id_requisito' => $getRequisitoId('DOC_PROPIEDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_excavacion, 'id_requisito' => $getRequisitoId('CROQUIS_PLANO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_excavacion, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_excavacion, 'id_requisito' => $getRequisitoId('ESTUDIO_IMPACTO_AMB'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_excavacion, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }

            // 13. RAMPAS, BANQUETAS Y GUARNICIONES
            $id_rampas = $getTramiteId('RAMPAS, BANQUETAS Y GUARNICIONES');
            if ($id_rampas) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_rampas, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rampas, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rampas, 'id_requisito' => $getRequisitoId('CROQUIS_PLANO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rampas, 'id_requisito' => $getRequisitoId('DESC_TECNICA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rampas, 'id_requisito' => $getRequisitoId('FOTOS_PREVIAS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rampas, 'id_requisito' => $getRequisitoId('AUTORIZACION_INAH'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_rampas, 'id_requisito' => $getRequisitoId('VISTO_BUENO_PC'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_rampas, 'id_requisito' => $getRequisitoId('COMPROMISO_ACCESIBILIDAD'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rampas, 'id_requisito' => $getRequisitoId('PAGO_PREDIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_rampas, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }

            // 14. OCUPACIÓN TEMPORAL (Permiso para Ocupación Temporal de la Vía Pública)
            $id_ocupacion = $getTramiteId('OCUPACIÓN TEMPORAL');
            if ($id_ocupacion) {
                // $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('SOLICITUD_FIRMA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('ID_OFICIAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('CROQUIS_PLANO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('FOTOS_PREVIAS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('JUSTIFICACION_USO_TEMPORAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('PROGRAMA_LIMPIEZA'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('AUTORIZACION_INAH'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('AUTORIZACION_TRANSITO'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('AUTORIZACION_AMBIENTAL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('VISTO_BUENO_PC'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now]; 
                $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('SEGURO_RESP_CIVIL'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
                $requisitosData[] = ['id_tramite_catalogo' => $id_ocupacion, 'id_requisito' => $getRequisitoId('PAGO_DERECHOS'), 'obligatorio' => $OBLIGATORIO, 'activo' => true, 'editable' => true, 'created_at' => $now, 'updated_at' => $now];
            }

            // ... Continúa el mapeo para los trámites restantes (8, 9, 10, 11, 12) de manera similar...

            // Inserción masiva de los requisitos
            if (!empty($requisitosData)) {
                // Elimina posibles duplicados si la migración se corre múltiples veces
                $chunks = array_chunk($requisitosData, 500); // chunking para grandes cantidades
                foreach ($chunks as $chunk) {
                    DB::table('catalogo_tramites_requisitos')->insert($chunk);
                }
            }

            // Si todo salió bien, guardamos cambios permanentemente
            DB::commit();
        } catch (\Exception $e) {
            // Si hay CUALQUIER error, deshacemos todo (incluso la creación de la tabla)
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            
            // Borramos la tabla si alcanzó a crearse antes del error de datos
            Schema::dropIfExists('catalogo_tramites_requisitos');

            // Mostramos el mensaje de error en la consola
            throw new \Exception("Error en " . $e->getFile() . " línea " . $e->getLine() . ": " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_tramites_requisitos');
    }
};
