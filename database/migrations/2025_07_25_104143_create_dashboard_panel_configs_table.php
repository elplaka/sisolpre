<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dashboard_panel_configs', function (Blueprint $table) {
            $table->id();
            // Clave foránea para la configuración por usuario, puede ser nula para global.
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            // Clave única para el panel.
            $table->string('slot_key');
            // Tipo de componente a renderizar.
            $table->string('panel_type');
            // JSON para almacenar un array de roles con acceso al panel. Es nulo para paneles globales.
            $table->json('roles')->nullable();
            // Orden de visualización del panel.
            $table->integer('order')->default(0);
            $table->timestamps();

            // Asegura que no haya dos paneles con la misma clave para el mismo usuario.
            $table->unique(['user_id', 'slot_key']);
        });

        // Insertar registros iniciales de configuración global (user_id = null).
        // Se utiliza json_encode para guardar los roles como un array JSON.
        DB::table('dashboard_panel_configs')->insert([
            [
                'user_id'    => null,
                'slot_key'   => 'panel0',
                'panel_type' => 'shortcuts',
                'roles'      => json_encode(['ADMINISTRADOR', 'AUXILIAR']), 
                'order'      => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => null,
                'slot_key'   => 'panel1',
                'panel_type' => 'latest_solicitudes',
                'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'AUXILIAR']), 
                'order'      => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => null,
                'slot_key'   => 'panel2',
                'panel_type' => 'solicitudes_by_month',
                'roles'      => null, 
                'order'      => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => null,
                'slot_key'   => 'panel3',
                'panel_type' => 'tramites_by_year',
                'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                'order'      => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => null,
                'slot_key'   => 'panel4',
                'panel_type' => 'solicitudes_by_property_type',
                'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']),
                'order'      => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => null,
                'slot_key'   => 'panel5',
                'panel_type' => 'people_solicitudes',
                'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']), 
                'order'      => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => null,
                'slot_key'   => 'panel6',
                'panel_type' => 'solicitudes_by_location',
                'roles'      => json_encode(['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR']), 
                'order'      => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_panel_configs');
    }
};