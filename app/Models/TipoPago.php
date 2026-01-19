<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPago extends Model
{
    use HasFactory;

    protected $table = 'tipo_pago'; // Nombre de la tabla
    public $timestamps = false;     // Si no tienes created_at / updated_at

    protected $fillable = [
        'nombre', // Efectivo, Transferencia, Tarjeta
    ];

    // Relación con Tickets
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id_tipo_pago');
    }
}
