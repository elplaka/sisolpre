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
        ];

        /**
         * Get the solicitud that owns the referencia.
         */
        public function solicitud()
        {
            return $this->belongsTo(Solicitud::class);
        }
    }