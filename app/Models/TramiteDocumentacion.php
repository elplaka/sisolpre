<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TramiteDocumentacion extends Model
{
    use HasFactory;

    protected $table = 'tramites_documentacion';

    protected $fillable = [
        'id_tramite',
        'id_requisito_documentacion',
        'entregada',
    ];

    public function requisitoDocumentacion()
    {
        return $this->belongsTo(RequisitoDocumentacion::class, 'id_requisito_documentacion');
    }

    public function tramite()
    {
        return $this->belongsTo(Tramite::class, 'id_tramite');
    }

    public function historial()
    {
        return $this->morphMany(HistorialCambio::class, 'auditable');
    }
}
