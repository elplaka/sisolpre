<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConstanciaNumeroOficial extends Model
{
    use HasFactory;

    protected $table = 'constancias_numero_oficial';

    protected $casts = [
        'fecha_emision' => 'date:Y-m-d',
        'fecha_expiracion' => 'date:Y-m-d',
    ];

    protected $fillable = [
        'id_tramite',
        'ano_oficio',
        'prefijo_oficio',
        'consecutivo_oficio',
        'id_plantilla',
        'id_propiedad',
        'fecha_emision',
        'fecha_expiracion',
        'numero_asignado',
        'numero_asignado_letra',
        'id_justificacion',
        'id_usuario',
        'id_autoridad_firmante'
    ];

    public function tramite()
    {
        return $this->belongsTo(Tramite::class, 'id_tramite');
    }

    /**
     * La propiedad asociada (aunque ya viene por el trámite, es útil tenerla directa).
     */
    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class, 'id_propiedad');
    }

    /**
     * El usuario que generó el oficio.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * La autoridad que firma el documento (Director/Secretario).
     */
    public function autoridadFirmante()
    {
        return $this->belongsTo(AutoridadFirmante::class, 'id_autoridad_firmante');
    }

    // En ambos modelos añade esta relación:
    public function historial()
    {
        return $this->morphMany(HistorialCambio::class, 'auditable');
    }
}
