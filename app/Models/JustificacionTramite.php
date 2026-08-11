<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JustificacionTramite extends Model
{
    use HasFactory;

    protected $table = 'justificaciones_tramites';

    protected $fillable = ['id_tramite', 'motivo'];

    public function tramite()
    {
        // Una justificación pertenece a (belongsTo) un trámite
        return $this->belongsTo(Tramite::class, 'id_tramite');
    }

    public function historial()
    {
        return $this->morphMany(HistorialCambio::class, 'auditable');
    }
}
