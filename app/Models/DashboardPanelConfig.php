<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardPanelConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slot_key',
        'panel_type',
        'order',
    ];

    // Si estás usando la configuración por usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}