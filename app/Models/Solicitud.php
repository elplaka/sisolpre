<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes';

    protected $fillable = [
        'fecha',
        'id_solicitante',
        'id_area',
        'id_estatus',
        'id_localidad_beneficiada',
        'peticion',
        'id_tipo_apoyo',
        'cantidad_aprobada',
        'observaciones',
        'id_user',
        'fecha_resolucion'
    ];

    public function estatus()
    {
        return $this->belongsTo(EstatusSolicitud::class, 'id_estatus');
    }

    public function historials()
    {
        return $this->hasMany(SolicitudHistorial::class, 'solicitud_id');
    }

    // Relación con el usuario (por si acaso también la necesitas)
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function solicitante()
    {
        return $this->belongsTo(Solicitante::class, 'id_solicitante');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    public function localidad_beneficiada(): BelongsTo
    {
        return $this->belongsTo(Localidad::class, 'id_localidad_beneficiada');
    }
}
