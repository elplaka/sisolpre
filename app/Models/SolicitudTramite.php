<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudTramite extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_tramites';

    protected $fillable = [
        'id_solicitud',
        'id_tramite',
    ];

    public function tramite()
    {
        return $this->belongsTo(CatalogoTramite::class, 'id_tramite');
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'id_solicitud');
    }
}
