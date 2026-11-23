<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Borra todo el contenido actual de la tabla
            DB::table('dashboard_panel_configs')->delete();

            DB::table('dashboard_panel_configs')->insert([
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel0',
                    'panel_type' => 'solicitudes_totales',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 0,
                    'created_at' => '2026-10-01 09:26:54',
                    'updated_at' => '2026-10-01 09:26:54',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel1',
                    'panel_type' => 'solicitudes_apoyo_entregado',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 1,
                    'created_at' => '2026-10-01 09:26:54',
                    'updated_at' => '2026-10-01 09:26:54',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel2',
                    'panel_type' => 'solicitudes_en_tramite',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 2,
                    'created_at' => '2026-10-01 09:26:54',
                    'updated_at' => '2026-10-01 09:26:54',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel3',
                    'panel_type' => 'solicitudes_aprobadas',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 3,
                    'created_at' => '2026-10-01 09:26:54',
                    'updated_at' => '2026-10-01 09:26:54',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel4',
                    'panel_type' => 'latest_solicitudes',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR', 'AUXILIAR']),
                    'order'      => 4,
                    'created_at' => '2026-10-01 09:26:54',
                    'updated_at' => '2026-10-01 09:26:54',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel5',
                    'panel_type' => 'solicitudes_by_month',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 5,
                    'created_at' => '2026-10-01 09:26:54',
                    'updated_at' => '2026-10-01 09:26:54',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel6',
                    'panel_type' => 'solicitudes_tiempo_entrega',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 6,
                    'created_at' => '2026-10-01 09:26:54',
                    'updated_at' => '2026-10-01 09:26:54',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel7',
                    'panel_type' => 'solicitudes_con_rezago',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 7,
                    'created_at' => '2026-10-18 19:57:12',
                    'updated_at' => '2026-10-18 19:57:12',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel8',
                    'panel_type' => 'solicitudes_ciudadanos_atendidos',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 8,
                    'created_at' => '2026-10-19 12:22:35',
                    'updated_at' => '2026-10-19 12:22:35',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel9',
                    'panel_type' => 'solicitudes_monto_entregado',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 9,
                    'created_at' => '2026-10-19 12:22:35',
                    'updated_at' => '2026-10-19 12:22:35',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel10',
                    'panel_type' => 'solicitudes_peticiones',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 10,
                    'created_at' => '2026-10-19 12:22:35',
                    'updated_at' => '2026-10-19 12:22:35',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel11',
                    'panel_type' => 'solicitudes_by_location',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 11,
                    'created_at' => '2026-10-19 12:22:35',
                    'updated_at' => '2026-10-19 12:22:35',
                ],
                [
                    'user_id'    => null,
                    'slot_key'   => 'panel12',
                    'panel_type' => 'solicitudes_by_area',
                    'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                    'order'      => 12,
                    'created_at' => '2026-10-19 12:22:35',
                    'updated_at' => '2026-10-19 12:22:35',
                ],
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('dashboard_panel_configs')->delete();
    }
};
