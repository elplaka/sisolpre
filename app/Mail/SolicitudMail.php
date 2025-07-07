<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue; 

class SolicitudMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $solicitud;

    public function __construct($solicitud)
    {
        $this->solicitud = $solicitud;
    }

    public function build()
    {
        $idFormateado = str_pad($this->solicitud->id, 4, '0', STR_PAD_LEFT);

        return $this->view('solicitudes.email')
                    ->subject('Registro de Solicitud ' . $idFormateado)
                    ->with([
                        'solicitud' => $this->solicitud,
                    ]);
    }
}