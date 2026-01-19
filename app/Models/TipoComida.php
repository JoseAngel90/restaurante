<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoComida extends Model
{
    use HasFactory;

    protected $table = 'tipo_comida';
    protected $fillable = ['descripcion',];
    public $timestamps = false; 

    // Relación: un tipo de comida tiene muchas comidas
    // TipoComida.php
public function comidas()
{
    // hasManyThrough(Target, Through, foreignKeyOnThrough, foreignKeyOnTarget, localKey, localKeyOnThrough)
    return $this->hasManyThrough(
        Comida::class,         // Modelo final
        SubtipoComida::class,  // Modelo intermedio
        'id_tipo_comida',      // FK de SubtipoComida hacia TipoComida
        'id_subtipo_comida',   // FK de Comida hacia SubtipoComida
        'id',                  // PK de TipoComida
        'id'                   // PK de SubtipoComida
    );
}


    public function subtipos() 
    {
        return $this->hasMany(SubtipoComida::class, 'id_tipo_comida');
    }

}
