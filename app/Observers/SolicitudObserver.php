<?php

namespace App\Observers;

use App\Models\Solicitud;
use Illuminate\Support\Facades\Auth;

class SolicitudObserver
{
    /**
     * Handle the Solicitud "created" event (Altas).
     */
    public function created(Solicitud $solicitud): void
    {
        $solicitud->historials()->create([
            'id_user' => Auth::id() ?? $solicitud->id_user,
            'accion' => 'CREACION',
            'datos_nuevos' => $solicitud->toArray(), // Guarda la solicitud completa al crearla
        ]);
    }

    /**
     * Handle the Solicitud "updated" event (Cambios).
     */
    public function updated(Solicitud $solicitud): void
    {
        // Obtiene solo los campos que sufrieron modificaciones
        $changes = $solicitud->getChanges();

        // VALIDACIÓN: Si no hay cambios reales, detenemos la ejecución y NO guardamos historial
        if (empty($changes)) {
            return;
        }

        // Filtramos los valores anteriores exclusivamente de los campos que cambiaron
        $original = [];
        foreach ($changes as $field => $newValue) {
            $original[$field] = $solicitud->getOriginal($field);
        }

        // Registramos el historial solo porque SÍ hubo cambios
        $solicitud->historials()->create([
            'id_user' => Auth::id() ?? $solicitud->id_user, // ¿Quién hizo el cambio?
            'accion' => 'ACTUALIZACION',
            'datos_anteriores' => $original, // Valor anterior de lo que cambió
            'datos_nuevos' => $changes,       // Valor nuevo de lo que cambió
        ]);
    }
}
