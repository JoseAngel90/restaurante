<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';

    protected $fillable = [
        'nombre',
        'email',
        'password',
        'id_rol',
        'activo'
    ];

    protected $hidden = [
        'password',
    ];

    public $timestamps = false;

    // bcrypt automático solo si la contraseña no está ya hasheada
    public function setPasswordAttribute($password)
    {
        if (!empty($password) && !\Illuminate\Support\Str::startsWith($password, '$2y$')) {
            $this->attributes['password'] = bcrypt($password);
        } else {
            $this->attributes['password'] = $password;
        }
    }

    // Relación con Rol
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    // Relación con pedidos que generó este usuario
    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'id_usuario');
    }

    // En Usuario.php
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id_cliente', 'id');
    }
}
