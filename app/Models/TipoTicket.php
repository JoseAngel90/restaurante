<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTicket extends Model
{
    use HasFactory;

    protected $table = 'tipo_ticket'; // Nombre de la tabla
    public $timestamps = false;       // Sin created_at / updated_at

    protected $fillable = [
        'nombre', // Cancelado, Pagado, Pendiente
    ];

    // Relación con Tickets
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id_tipo_ticket');
    }
}
