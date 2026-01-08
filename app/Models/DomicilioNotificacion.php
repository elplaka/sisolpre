<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomicilioNotificacion extends Model
{
    use HasFactory;

    protected $table = 'domicilios_notificaciones';

        protected $fillable = [
        'direccion',
    ];
}
