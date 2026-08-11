<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoridadFirmante extends Model
{
    use HasFactory;

    protected $table = 'autoridades_firmantes';

    protected $fillable = [
        'nombre',
        'cargo',
        'titulo',
        'activa',
    ];
}
