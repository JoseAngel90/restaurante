<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comida extends Model
{
    use HasFactory;

    protected $table = 'comidas';

    protected $fillable = [
        'nombre',
        'imagen',
        'abreviatura_op',
        'precio',
        'id_tipo_comida',
        'id_subtipo_comida',
        'disponible',
    ];

    public $timestamps = false;

    // Relación: pertenece a un tipo de comida
    public function tipoComida()
    {
        return $this->belongsTo(TipoComida::class, 'id_tipo_comida');
    }

    public function subtipoComida() 
    {
    return $this->belongsTo(SubtipoComida::class, 'id_subtipo_comida');
    }

    

    public function pedidoDetalles()
    {
        return $this->hasMany(PedidoDetalle::class, 'id_comida');
    }

    public function disponibilidades()
    {
        return $this->hasMany(DisponibilidadComidaDia::class, 'id_comida');
    }
}
