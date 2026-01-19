<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisponibilidadComidaDia extends Model
{
    use HasFactory;

    protected $table = 'disponibilidad_comida_dia';
    public $timestamps = false;

    protected $fillable = [
        'id_comida',
        'fecha',
        'disponible',
      
    ];

    // Relación con Comida
    public function comida()
    {
        return $this->belongsTo(Comida::class, 'id_comida');
    }
}
