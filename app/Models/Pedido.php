<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedido'; // Si tu tabla no sigue la convención plural

    public $timestamps = false; // Deshabilitar si tu tabla no tiene created_at / updated_at

    protected $fillable = [
        'id_cliente',
        'id_usuario',
        'id_tipo_pedido',
        'fecha_pedido',
        'fecha_entrega',
        'notas'
    ];

    // Relación con Cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    // Relación con Usuario (mesero o cajero)
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Relación con TipoPedido (estado del pedido)
    public function tipoPedido()
    {
        return $this->belongsTo(TipoPedido::class, 'id_tipo_pedido');
    }

    // Relación con DetallePedido
    public function detalles()
    {
        return $this->hasMany(PedidoDetalle::class, 'id_pedido');
    }

    // Relación con Ticket
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id_pedido');
    }
    
}
