<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes';

    protected $fillable = [
        'fecha_ingreso',
        'id_contacto',
        'id_propiedad',
        'id_destino_obra',
        'id_sector',
        'id_estatus',
        'folio_digital',
        'token_acceso',
        'folio',
        'fecha_aceptacion'
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

    public function tramitesAsignados()
    {
        return $this->hasMany(Tramite::class, 'id_solicitud');
    }

    public function destino_obra()
    {
        return $this->belongsTo(DestinoObra::class, 'id_destino_obra');
    }

    public function sectorTramite()
    {
        return $this->belongsTo(SectorTramite::class, 'id_sector');
    }

    public function referencia()
    {
        return $this->hasOne(SolicitudReferencia::class, 'id_solicitud');
    }

    public function croquis_aux()
    {
        return $this->hasOne(CroquisAux::class, 'id_solicitud');
    }

    public function razon_social()
    {
        return $this->hasOne(SolicitudRazonSocial::class, 'id_solicitud');
    }

    public function aceptada()
    {
        return $this->hasOne(SolicitudAceptada::class, 'id', 'folio');
    }

    // --- ¡Este es el accesor clave! ---
    protected function groupedTramites(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Cargar la relación si no está ya cargada para evitar N+1 en caso de olvido
                if (!$this->relationLoaded('tramites') || !$this->tramites->first()?->relationLoaded('tramite.tipoTramite')) {
                    $this->loadMissing('tramites.tramite.tipoTramite');
                }

                return $this->tramites
                    ->map(function ($solicitudTramite) {
                        if ($solicitudTramite->tramite && $solicitudTramite->tramite->tipoTramite) {
                            return [
                                'tipo_tramite_id' => $solicitudTramite->tramite->tipoTramite->id,
                                'tipo_tramite_nombre' => $solicitudTramite->tramite->tipoTramite->nombre,
                                'tramite_id' => $solicitudTramite->tramite->id,
                                'tramite_nombre' => $solicitudTramite->tramite->nombre,
                                // Agrega otros campos del trámite si los necesitas en tu PDF
                            ];
                        }
                        return null;
                    })
                    ->filter()
                    ->sortBy('tramite_id') // Ordena alfabéticamente por nombre del trámite dentro de cada tipo
                    ->groupBy('tipo_tramite_nombre'); // Agrupa por el nombre del tipo de trámite
            }
        );
    }

    public function requisitos_docs()
    {
        // El método belongsToMany define la relación de Muchos a Muchos.
        return $this->belongsToMany(
            RequisitoDocumentacion::class, // El modelo con el que se relaciona
            'solicitudes_documentacion',   // 1. Nombre explícito de la tabla pivote (importante)
            'id_solicitud',                // 2. Nombre de la clave foránea local en la tabla pivote
            'id_requisito_documentacion'   // 3. Nombre de la clave foránea relacionada en la tabla pivote
        )
            // Usamos withPivot() para incluir cualquier columna adicional que tengas en la tabla pivote.
            // Por ejemplo, si añades 'entregado', debes incluirlo aquí:
            // ->withPivot('entregado');

            // Si tienes timestamps en la tabla pivote, inclúyelos con withTimestamps
            ->withTimestamps();
    }

    public function documentos()
    {
        return $this->hasMany(SolicitudDocumentacion::class, 'id_solicitud');
    }
}
