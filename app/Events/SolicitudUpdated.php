<?php

namespace App\Events;

use App\Models\Solicitud; // Asegúrate de importar tu modelo
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SolicitudUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Solicitud $solicitud;
    public string $action; // 'created', 'updated', 'deleted'

    /**
     * Create a new event instance.
     */
    public function __construct(Solicitud $solicitud, string $action)
    {
        $this->solicitud = $solicitud;
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // El canal público al que se suscribirán los clientes
        return [
            new Channel('solicitudes'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        // El nombre del evento que escuchará Laravel Echo
        return 'solicitud.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'solicitud' => $this->solicitud->load(
            'estatus',
            'contacto',
            'contacto.persona',
            'propiedad',
            'propiedad.contacto',
            'propiedad.contacto.persona',
            'propiedad.colonia',
            'propiedad.localidad',
            'propiedad.tipo',
            'destino_obra',
            'tramites',
            'tramites.tramite'
            ), // Carga relaciones si las necesitas en el frontend
            // 'solicitud_id' => $this->solicitud->id, // Puedes enviar solo el ID si prefieres
        ];
    }
}