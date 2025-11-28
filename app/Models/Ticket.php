<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'ticket';
    public $timestamps = false; // Ya que tu tabla no tiene created_at/updated_at

    protected $fillable = [
        'id_pedido',
        'id_tipo_pago',
        'id_cliente',
        'id_tipo_ticket',
        'total',
        'fecha_ticket',
        'monto_recibido',
        'cambio',
    ];



     protected $casts = [
        'fecha_ticket' => 'datetime',
        'total' => 'decimal:2',
        'monto_recibido' => 'decimal:2',
        'cambio' => 'decimal:2'
    ];
        
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class, 'id_tipo_pago');
    }

    public function tipoTicket()
    {
        return $this->belongsTo(TipoTicket::class, 'id_tipo_ticket');
    }

}
