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

}

