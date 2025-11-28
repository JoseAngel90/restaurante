<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubtipoComida extends Model
{
    use HasFactory;

    protected $table = 'subtipo_comida';
    protected $fillable = ['descripcion', 'id_tipo_comida'];
    public $timestamps = false;

    // Un subtipo pertenece a un tipo de comida
    public function tipoComida()
    {
        return $this->belongsTo(TipoComida::class, 'id_tipo_comida');
    }

    // Un subtipo puede tener muchas comidas
    public function comidas() {
        return $this->hasMany(Comida::class, 'id_subtipo_comida');
    }
}
