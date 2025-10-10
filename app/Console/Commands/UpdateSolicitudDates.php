<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Solicitud;


class UpdateSolicitudDates extends Command
{
    protected $signature = 'solicitudes:update-dates';
    protected $description = 'Updates the fecha_aceptacion field for existing solicitudes based on their updated_at timestamp.';

    public function handle()
    {
        $this->info('Starting to update solicitudes dates...');

        // Find all records where fecha_activacion is null
        $solicitudes = Solicitud::where('id_estatus', 99)->get();

        $updatedCount = 0;
        foreach ($solicitudes as $solicitud) {
            // Assign the value of updated_at to fecha_activacion
            $solicitud->fecha_aceptacion = $solicitud->fecha_ingreso;
            $solicitud->save();
            $updatedCount++;
        }

        $this->info("Finished updating {$updatedCount} solicitudes.");
    }
}
