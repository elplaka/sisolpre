<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tramite extends Model
{
    protected $appends = ['documento_generado'];

    protected $fillable = [
        'id_solicitud',
        'id_tramite',
        'fecha_inicio',
        'fecha_fin',
        'id_contacto',
        'id_propiedad',
        'id_estatus'
    ];

    // Casteo de fechas para facilitar el formato en Vue/Inertia
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function getDocumentoGeneradoAttribute()
    {
        // Esta lógica revisa cuál relación tiene datos según el tipo de trámite
        if ($this->constanciaNumeroOficial) {
            $doc = $this->constanciaNumeroOficial;
            $doc->tipo_slug = 'constancia-numero-oficial'; // Un identificador para tus rutas
            return $doc;
        }

        if ($this->constanciaZonificacion) {
            $doc = $this->constanciaZonificacion;
            $doc->tipo_slug = 'constancia-zonificacion';
            return $doc;
        }

        return null;
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'id_solicitud');
    }

    public function tipoTramite()
    {
        return $this->belongsTo(CatalogoTramite::class, 'id_tramite');
    }

    public function contacto()
    {
        return $this->belongsTo(Contacto::class, 'id_contacto');
    }

    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class, 'id_propiedad');
    }

    public function estatus()
    {
        return $this->belongsTo(EstatusTramite::class, 'id_estatus');
    }

    public function constanciaNumeroOficial()
    {
        return $this->hasOne(ConstanciaNumeroOficial::class, 'id_tramite');
    }

    public function documentos()
    {
        return $this->belongsToMany(
            RequisitoDocumentacion::class, // Modelo relacionado
            'tramites_documentacion',      // Tabla pivote
            'id_tramite',                  // FK en pivote para este modelo
            'id_requisito_documentacion'   // FK en pivote para el modelo relacionado
        )
            ->withPivot('entregado')
            ->withTimestamps(); // Para que Laravel gestione created_at y updated_at en el pivote
    }

    public function justificacion()
    {
        // Un trámite tiene una (hasOne) justificación
        return $this->hasOne(JustificacionTramite::class, 'id_tramite');
    }

    // En ambos modelos añade esta relación:
    public function historial()
    {
        return $this->morphMany(HistorialCambio::class, 'auditable');
    }
}
