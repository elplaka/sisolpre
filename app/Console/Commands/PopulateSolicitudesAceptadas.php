<?php

namespace App\Console\Commands;

use App\Models\Solicitud;
use App\Models\SolicitudAceptada;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class PopulateSolicitudesAceptadas extends Command
{
    protected $signature = 'solicitudes:populate-aceptadas';
    protected $description = 'Populates the solicitudes_aceptadas table from existing solicitudes and updates the folio.';

    public function handle()
    {
        try {
            $this->info('Starting to populate solicitudes_aceptadas table...');

            Solicitud::orderBy('fecha_ingreso', 'asc')
                ->orderBy('updated_at', 'asc')
                ->chunk(100, function ($solicitudes) {
                    foreach ($solicitudes as $solicitud) {
                        DB::transaction(function () use ($solicitud) {
                            // 1. Insert a record into solicitudes_aceptadas
                            $solicitudAceptada = SolicitudAceptada::create([
                                'id_solicitud' => $solicitud->id,
                                'activa'       => true
                            ]);

                            // 2. Prepare the update data for the 'solicitud' record
                            $updateData = ['folio' => $solicitudAceptada->id];

                            // ✅ Add the conditional logic here
                            if ($solicitud->id_estatus == 99) {
                                $updateData['fecha_aceptacion'] = $solicitud->fecha_ingreso;
                            }

                            // 3. Update the 'solicitud' record
                            $solicitud->update($updateData);
                        });
                    }
                });

            $this->info('Finished populating solicitudes_aceptadas table.');
        } catch (Throwable $e) {
            $this->error('An error occurred. Rolling back the operation.');
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}