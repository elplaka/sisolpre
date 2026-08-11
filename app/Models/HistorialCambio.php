<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialCambio extends Model
{
    protected $fillable = [
        'auditable_id',
        'auditable_type',
        'id_usuario',
        'id_tramite_principal',
        'accion',
        'valores_anteriores',
        'valores_nuevos'
    ];

    // Esto es magia de Laravel: convierte el JSON de la DB en un array de PHP
    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos'     => 'array',
    ];

    // Relación para saber qué objeto se auditó
    public function auditable()
    {
        return $this->morphTo();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
