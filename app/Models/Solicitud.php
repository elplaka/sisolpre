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
        'id_solicitante',
        'id_propietario',
        'id_propiedad',
        'img_croquis',
        'id_destino_obra',
        'id_estatus'
    ];

    public function representante()
    {
        return $this->belongsTo(Solicitante::class, 'id_solicitante_representante');
    }

    public function estatus()
    {
        return $this->belongsTo(EstatusSolicitud::class, 'id_estatus');
    }

    public function solicitante()
    {
        return $this->belongsTo(Solicitante::class, 'id_solicitante');
    }

    public function propietario()
    {
        return $this->belongsTo(Solicitante::class, 'id_propietario');
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

