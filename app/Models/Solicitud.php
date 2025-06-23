<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes';

    protected $fillable = [
        'fecha_ingreso',
        'id_contacto',
        'id_propiedad',
        'id_destino_obra',
        'id_estatus',
        'folio_digital'
    ];

    public function estatus()
    {
        return $this->belongsTo(EstatusSolicitud::class, 'id_estatus');
    }

    public function contacto()
    {
        return $this->belongsTo(Contacto::class, 'id_contacto');
    }

    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class, 'id_propiedad');
    }

    public function tramites()
    {
        return $this->hasMany(SolicitudTramite::class, 'id_solicitud');
    }

    public function destino_obra()
    {
        return $this->belongsTo(DestinoObra::class, 'id_destino_obra');
    }

}

