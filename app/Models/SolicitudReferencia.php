<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class SolicitudReferencia extends Model
    {
        use HasFactory;

        protected $table = 'solicitud_referencias';

        protected $fillable = [
            'id_solicitud',
            'contenido',
            'id_tipo_propiedad',
            'id_localidad',
        ];

        /**
         * Get the solicitud that owns the referencia.
         */
        public function solicitud()
        {
            return $this->belongsTo(Solicitud::class);
        }

        public function tipo_propiedad()
        {
            return $this->belongsTo(TipoPropiedad::class, 'id_tipo_propiedad');
        }

        public function localidad()
        {
            return $this->belongsTo(Localidad::class, 'id_localidad');
        }
    }