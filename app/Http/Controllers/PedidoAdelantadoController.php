<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DisponibilidadComidaDia;
use App\Models\Comida;
use DB;

class PedidoAdelantadoController extends Controller
{
 public function store(Request $request)
{
    
    DB::beginTransaction();

    try {

        if(empty($request->comidas)){
            throw new \Exception("No llegaron comidas");
        }

        $cliente = \App\Models\Cliente::firstOrCreate(
            ['telefono' => $request->cliente_telefono],
            ['nombre' => $request->cliente_nombre]
        );

        $tipoPedido = \App\Models\TipoPedido::where('nombre','Pendiente')->firstOrFail();

        $pedidoId = DB::table('pedido')->insertGetId([
            'id_cliente'     => $cliente->id,
            'id_usuario'     => auth()->id(),
            'id_tipo_pedido' => $tipoPedido->id,
            'notas'          => $request->notas,
            'fecha_pedido'   => now(),
            'fecha_entrega'  => $request->tiempo_entrega,
        ]);

        $total = 0;

        foreach ($request->comidas as $item) {

            $comida = \App\Models\Comida::findOrFail($item['id']);
            $cantidad = (int)$item['cantidad'];
            $precio = $comida->precio ?? 0;
            $subtotal = $precio * $cantidad;

            DB::table('pedido_detalle')->insert([
                'id_pedido'       => $pedidoId,
                'id_comida'       => $comida->id,
                'cantidad'        => $cantidad,
                'precio_unitario' => $precio,
                'subtotal'        => $subtotal,
            ]);

            $total += $subtotal;
        }

        
        DB::commit();

        return response()->json([
            'ok' => true,
            'total' => $total
        ]);

    } catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([
            'ok' => false,
            'error' => $e->getMessage(),
            'linea' => $e->getLine()
        ], 500);
    }
}

}

