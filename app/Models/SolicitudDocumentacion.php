<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot; // Importar Pivot

// Usamos 'Pivot' para indicar que es una tabla intermedia, aunque Model también funciona.

class SolicitudDocumentacion extends Pivot 
{
    protected $table = 'solicitudes_documentacion';

    protected $fillable = [
        'id_solicitud',
        'id_requisito_documentacion',
    ];

    protected $primaryKey = ['id_solicitud', 'id_requisito_documentacion'];
    public $incrementing = false; // Indica que las claves no son autoincrementables
    
    // Opcionalmente, define relaciones con los modelos principales
    
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'id_solicitud');
    }

    public function requisitoDocumentacion()
    {
        return $this->belongsTo(RequisitoDocumentacion::class, 'id_requisito_documentacion');
    }
}