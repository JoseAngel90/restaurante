<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoDetalle extends Model
{
    use HasFactory;

    protected $table = 'pedido_detalle'; // Si tu tabla no sigue la convención plural

    public $timestamps = false; // Deshabilitar si no tienes created_at / updated_at

    protected $fillable = [
        'id_pedido',
        'id_comida',
        'cantidad',
        'precio_unitario',
        'subtotal'
    ];

    // Relación con Pedido
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }

    // Relación con Comida
    public function comida()
    {
        return $this->belongsTo(Comida::class, 'id_comida');
    }
}
