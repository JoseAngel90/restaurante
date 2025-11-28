<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPedido extends Model
{
    use HasFactory;

    // Nombre explícito de la tabla si no sigue la convención plural
    protected $table = 'tipo_pedido';

    // Clave primaria (opcional si es 'id')
    protected $primaryKey = 'id';

    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'nombre',
    ];

    public $timestamps = false;

    // Relación con pedidos
    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'id_tipo_pedido');
    }
}
