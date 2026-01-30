<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\TipoPedido;
use App\Models\Comida;
use App\Models\PedidoDetalle;
use App\Models\DisponibilidadComidaDia;
use App\Models\Ticket;
use App\Models\TipoTicket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HacerPedidoController extends Controller
{
    /**
     * Mostrar formulario para crear un nuevo pedido
     */
    public function create()
    {
        $hoy = Carbon::today()->format('Y-m-d');

        // Traer todas las comidas disponibles hoy con stock > 0
        $comidasHoy = DisponibilidadComidaDia::with('comida.tipoComida')
            ->where('fecha', $hoy)
            ->whereHas('comida', function ($q) {
                $q->where('disponible', '>', 0);
            })
            ->get();

        return view('hacer-pedido', compact('comidasHoy'));
    }

    /**
     * Guardar un nuevo pedido
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_nombre' => 'required|string|max:100',
            'cliente_telefono' => 'required|string|max:20',
            'estado_pedido' => 'required|string|max:50',
            'detalle' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $cliente = Cliente::firstOrCreate(
                ['telefono' => $request->cliente_telefono],
                ['nombre' => $request->cliente_nombre]
            );

            $tipo = TipoPedido::firstOrCreate(['nombre' => $request->estado_pedido]);

            $pedido = Pedido::create([
                'id_cliente' => $cliente->id,
                'id_usuario' => auth()->id() ?? 1,
                'id_tipo_pedido' => $tipo->id,
                'fecha_pedido' => now(),
                'notas' => $request->notas,
            ]);

            $detalleData = $request->input('detalle', []);
            $totalPedido = 0;
            $tiposIncluidos = ['SOPA', 'ENSALADAS'];

            // Reorganizar datos: agrupar por paquete
            $paquetesPorNumero = [];

            foreach ($detalleData as $comidaId => $arrayData) {
                $comida = Comida::with('tipoComida')->find($comidaId);
                if (!$comida) continue;

                $cantidadesPorPaquete = $arrayData['cantidad'] ?? [];

                foreach ($cantidadesPorPaquete as $numeroPaquete => $cantidad) {
                    $cantidad = intval($cantidad);
                    if ($cantidad <= 0) continue;

                    if (!isset($paquetesPorNumero[$numeroPaquete])) {
                        $paquetesPorNumero[$numeroPaquete] = [];
                    }

                    $paquetesPorNumero[$numeroPaquete][$comidaId] = [
                        'comida' => $comida,
                        'cantidad' => $cantidad
                    ];
                }
            }

            // Validar que cada paquete tenga al menos un platillo fuerte
           /* foreach ($paquetesPorNumero as $numeroPaquete => $comidas) {
                $tienePlatoFuerte = false;

                foreach ($comidas as $comidaId => $data) {
                    $tipoComida = strtoupper($data['comida']->tipoComida->descripcion ?? '');
                    if ($tipoComida === 'PLATO FUERTE') {
                        $tienePlatoFuerte = true;
                        break;
                    }
                }

                if (!$tienePlatoFuerte) {
                    DB::rollBack();
                    return redirect()->back()->with('error', "Paquete {$numeroPaquete}: Debes agregar un platillo fuerte.");
                }
            }*/

            // Procesar cada paquete
            foreach ($paquetesPorNumero as $numeroPaquete => $comidas) {
                // Verificar si este paquete tiene el combo perfecto (PF + Sopa + Ensaladas)
                $tiposEnPaquete = [];
                $cantidadPlatoFuerte = 0;
                $tieneSopa = false;
                $tieneEnsaladas = false;

                foreach ($comidas as $comidaId => $data) {
                    $tipoComida = strtoupper($data['comida']->tipoComida->descripcion ?? '');
                    
                    if ($tipoComida === 'PLATO FUERTE') {
                        $cantidadPlatoFuerte += $data['cantidad'];
                    }
                    if ($tipoComida === 'SOPA') {
                        $tieneSopa = true;
                    }
                    if ($tipoComida === 'ENSALADAS') {
                        $tieneEnsaladas = true;
                    }
                    
                    if (!in_array($tipoComida, $tiposEnPaquete)) {
                        $tiposEnPaquete[] = $tipoComida;
                    }
                }

                // Verificar si tiene el combo perfecto (PF + Sopa + Ensaladas)
                // Ahora permite extras además del combo
                $tieneComboBase = $cantidadPlatoFuerte >= 1 && $tieneSopa && $tieneEnsaladas;
                $esComboPerFecto = $tieneComboBase;

                // Contadores para rastrear cuántas unidades de cada tipo ya se procesaron en este paquete
                $contadorPlatoFuerte = 0;
                $contadorSopa = 0;
                $contadorEnsaladas = 0;

                // Debug para verificar
                \Log::info("Paquete {$numeroPaquete}: PF={$cantidadPlatoFuerte}, Sopa={$tieneSopa}, Ensaladas={$tieneEnsaladas}, Tipos=".count($tiposEnPaquete).", ComboPerFecto={$esComboPerFecto}");

                foreach ($comidas as $comidaId => $data) {
                    $comida = $data['comida'];
                    $cantidad = $data['cantidad'];
                    $tipoComida = strtoupper($comida->tipoComida->descripcion ?? '');

                    // Validar stock
                    if ($comida->disponible < $cantidad) {
                        DB::rollBack();
                        return redirect()->back()->with('error', "No hay suficiente stock para {$comida->nombre} en paquete {$numeroPaquete}.");
                    }

                    \Log::info("Procesando: Paquete={$numeroPaquete}, Comida={$comida->nombre}, Tipo={$tipoComida}, Cantidad={$cantidad}, ComboPerFecto={$esComboPerFecto}");

                    // Procesar según el tipo y si es combo perfecto
                    if ($tipoComida === 'PLATO FUERTE') {
                        if ($esComboPerFecto) {
                            // Determinar cuántas unidades son del combo y cuántas son extras
                            $unidadesCombo = max(0, min(1 - $contadorPlatoFuerte, $cantidad));
                            $unidadesExtras = $cantidad - $unidadesCombo;
                            
                            // Combo perfecto: primer plato fuerte = $95, extras = precio original
                            $precioUnitario = $comida->precio ?? 0;
                            $subtotal = ($unidadesCombo * 95) + ($unidadesExtras * $precioUnitario);
                            
                            $contadorPlatoFuerte += $cantidad;
                        } else {
                            // Sin combo perfecto: precio normal del plato fuerte
                            $precioUnitario = $comida->precio ?? 0;
                            $subtotal = $cantidad * $precioUnitario;
                        }

                        PedidoDetalle::create([
                            'id_pedido' => $pedido->id,
                            'id_comida' => $comidaId,
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precioUnitario,
                            'subtotal' => $subtotal,
                            'numero_paquete' => $numeroPaquete,
                        ]);

                        $comida->decrement('disponible', $cantidad);
                        $totalPedido += $subtotal;

                    } elseif ($tipoComida === 'SOPA') {
                        if ($esComboPerFecto) {
                            // Determinar cuántas unidades están incluidas y cuántas son extras
                            $unidadesIncluidas = max(0, min(1 - $contadorSopa, $cantidad));
                            $unidadesExtras = $cantidad - $unidadesIncluidas;
                            
                            // Primera sopa incluida en combo ($0), extras = precio original
                            $precioUnitario = $comida->precio ?? 0;
                            $subtotal = $unidadesExtras * $precioUnitario;
                            
                            $contadorSopa += $cantidad;
                        } else {
                            // Sin combo perfecto: todas con precio
                            $precioUnitario = $comida->precio ?? 0;
                            $subtotal = $cantidad * $precioUnitario;
                        }

                        PedidoDetalle::create([
                            'id_pedido' => $pedido->id,
                            'id_comida' => $comidaId,
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precioUnitario,
                            'subtotal' => $subtotal,
                            'numero_paquete' => $numeroPaquete,
                        ]);

                        $comida->decrement('disponible', $cantidad);
                        $totalPedido += $subtotal;

                    } elseif ($tipoComida === 'ENSALADAS') {
                        if ($esComboPerFecto) {
                            // Determinar cuántas unidades están incluidas y cuántas son extras
                            $unidadesIncluidas = max(0, min(1 - $contadorEnsaladas, $cantidad));
                            $unidadesExtras = $cantidad - $unidadesIncluidas;
                            
                            // Primera ensalada incluida en combo ($0), extras = precio original
                            $precioUnitario = $comida->precio ?? 0;
                            $subtotal = $unidadesExtras * $precioUnitario;
                            
                            $contadorEnsaladas += $cantidad;
                        } else {
                            // Sin combo perfecto: todas con precio
                            $precioUnitario = $comida->precio ?? 0;
                            $subtotal = $cantidad * $precioUnitario;
                        }

                        PedidoDetalle::create([
                            'id_pedido' => $pedido->id,
                            'id_comida' => $comidaId,
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precioUnitario,
                            'subtotal' => $subtotal,
                            'numero_paquete' => $numeroPaquete,
                        ]);

                        $comida->decrement('disponible', $cantidad);
                        $totalPedido += $subtotal;

                    } else {
                        // Extras (Arroz, Papas, Postre, etc.)
                        // Siempre con precio completo
                        $precioUnitario = $comida->precio ?? 0;
                        $subtotal = $cantidad * $precioUnitario;

                        PedidoDetalle::create([
                            'id_pedido' => $pedido->id,
                            'id_comida' => $comidaId,
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precioUnitario,
                            'subtotal' => $subtotal,
                            'numero_paquete' => $numeroPaquete,
                        ]);

                        $comida->decrement('disponible', $cantidad);
                        $totalPedido += $subtotal;
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', "Pedido registrado correctamente. Total: \${$totalPedido}");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al registrar el pedido: ' . $e->getMessage());
        }
    }

    /**
     * Marcar un pedido como entregado
     */
    public function entregar($id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->fecha_entrega = now();
        $pedido->save();

        return redirect()->back()->with('success', 'Pedido entregado correctamente.');
    }

    /**
     * Actualizar un pedido existente
     */
    public function update(Request $request, Pedido $pedido)
    {
        $request->validate([
            'cliente_nombre' => 'required|string|max:100',
            'cliente_telefono' => 'required|string|max:20',
            'detalle' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            // Actualizar cliente
            Cliente::updateOrCreate(
                ['id' => $pedido->id_cliente],
                [
                    'nombre' => $request->cliente_nombre,
                    'telefono' => $request->cliente_telefono
                ]
            );

            // Actualizar estado si viene
            if ($request->filled('estado_pedido')) {
                $tipo = TipoPedido::firstOrCreate(['nombre' => $request->estado_pedido]);
                $pedido->id_tipo_pedido = $tipo->id;
            }

            $pedido->notas = $request->notas;
            $pedido->save();

            $normalizarTipo = function ($descripcion) {
                $t = strtoupper($descripcion ?? '');
                if (str_contains($t, 'PLATO FUERTE') || str_contains($t, 'PLATO')) {
                    return 'PLATO FUERTE';
                }
                if (str_contains($t, 'SOPA')) {
                    return 'SOPA';
                }
                if (str_contains($t, 'ENSAL')) {
                    return 'ENSALADAS';
                }
                return $t;
            };

            $detallesExistentes = PedidoDetalle::with('comida.tipoComida')
                ->where('id_pedido', $pedido->id)
                ->get();
            $paquetesExistentes = $detallesExistentes->groupBy(function ($detalle) {
                return $detalle->numero_paquete ?? 1;
            });

            $numeroPaqueteDefault = null;
            foreach ($paquetesExistentes as $numeroPaquete => $detallesPaquete) {
                $cantidadPlatoFuerte = 0;
                $tieneSopa = false;
                $tieneEnsaladas = false;

                foreach ($detallesPaquete as $detalle) {
                    $tipo = $normalizarTipo($detalle->comida->tipoComida->descripcion ?? '');
                    if ($tipo === 'PLATO FUERTE') {
                        $cantidadPlatoFuerte += $detalle->cantidad;
                    }
                    if ($tipo === 'SOPA') {
                        $tieneSopa = true;
                    }
                    if ($tipo === 'ENSALADAS') {
                        $tieneEnsaladas = true;
                    }
                }

                if ($cantidadPlatoFuerte >= 1 && $tieneSopa && $tieneEnsaladas) {
                    $numeroPaqueteDefault = intval($numeroPaquete);
                    break;
                }
            }

            if ($numeroPaqueteDefault === null) {
                $maxPaquete = $paquetesExistentes->keys()->max();
                $numeroPaqueteDefault = $maxPaquete ? intval($maxPaquete) : 1;
            }

            // Procesar detalle
            foreach ($request->detalle as $key => $item) {
                $cantidad = intval($item['cantidad'] ?? 0);
                $precio = $item['precio'] ?? null;
                $idComida = $item['id_comida'] ?? null;

                // Si es nuevo detalle
                if (is_string($key) && str_starts_with($key, 'nuevo_')) {
                    if (!$idComida || $cantidad <= 0) continue;

                    $comida = Comida::find($idComida);
                    if (!$comida || $cantidad > $comida->disponible) {
                        DB::rollBack();
                        return redirect()->back()->with('error', "No hay suficiente stock para {$comida->nombre}.");
                    }

                    PedidoDetalle::create([
                        'id_pedido' => $pedido->id,
                        'id_comida' => $idComida,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $comida->precio ?? 0,
                        'subtotal' => $cantidad * ($comida->precio ?? 0),
                        'numero_paquete' => $numeroPaqueteDefault,
                    ]);

                    $comida->decrement('disponible', $cantidad);
                    continue;
                }

                // Detalles existentes
                $detalle = PedidoDetalle::find(intval($key)) ?? PedidoDetalle::where('id_pedido', $pedido->id)->where('id_comida', $idComida)->first();
                if (!$detalle) continue;

                $comida = Comida::find($detalle->id_comida);
                if (!$comida) continue;

                // Ajustar stock y eliminar si cantidad es 0
                if ($cantidad <= 0) {
                    $comida->increment('disponible', $detalle->cantidad);
                    $detalle->delete();
                    continue;
                }

                // Ajustar diferencia
                $diff = $cantidad - $detalle->cantidad;
                if ($diff > 0) {
                    if ($diff > $comida->disponible) {
                        DB::rollBack();
                        return redirect()->back()->with('error', "No hay suficiente stock para {$comida->nombre}.");
                    }
                    $comida->decrement('disponible', $diff);
                } elseif ($diff < 0) {
                    $comida->increment('disponible', abs($diff));
                }

                // Actualizar detalle
                $detalle->cantidad = $cantidad;
                $detalle->save();
            }

            // Recalcular subtotales respetando combo perfecto por paquete
            $detallesActualizados = PedidoDetalle::with('comida.tipoComida')
                ->where('id_pedido', $pedido->id)
                ->get();
            $detallesPorPaquete = $detallesActualizados->groupBy(function ($detalle) {
                return $detalle->numero_paquete ?? 1;
            });

            foreach ($detallesPorPaquete as $numeroPaquete => $detallesPaquete) {
                $cantidadPlatoFuerte = 0;
                $tieneSopa = false;
                $tieneEnsaladas = false;

                foreach ($detallesPaquete as $detalle) {
                    $tipo = $normalizarTipo($detalle->comida->tipoComida->descripcion ?? '');
                    if ($tipo === 'PLATO FUERTE') {
                        $cantidadPlatoFuerte += $detalle->cantidad;
                    }
                    if ($tipo === 'SOPA') {
                        $tieneSopa = true;
                    }
                    if ($tipo === 'ENSALADAS') {
                        $tieneEnsaladas = true;
                    }
                }

                $esComboPerFecto = $cantidadPlatoFuerte >= 1 && $tieneSopa && $tieneEnsaladas;

                $contadorPlatoFuerte = 0;
                $contadorSopa = 0;
                $contadorEnsaladas = 0;

                foreach ($detallesPaquete->sortBy('id') as $detalle) {
                    $comida = $detalle->comida;
                    $cantidad = $detalle->cantidad;
                    $tipo = $normalizarTipo($comida->tipoComida->descripcion ?? '');
                    $precioBase = $comida->precio ?? $detalle->precio_unitario ?? 0;

                    if ($tipo === 'PLATO FUERTE') {
                        if ($esComboPerFecto) {
                            $unidadesCombo = max(0, min(1 - $contadorPlatoFuerte, $cantidad));
                            $unidadesExtras = $cantidad - $unidadesCombo;
                            $subtotal = ($unidadesCombo * 95) + ($unidadesExtras * $precioBase);
                            $contadorPlatoFuerte += $cantidad;
                        } else {
                            $subtotal = $cantidad * $precioBase;
                        }
                    } elseif ($tipo === 'SOPA') {
                        if ($esComboPerFecto) {
                            $unidadesIncluidas = max(0, min(1 - $contadorSopa, $cantidad));
                            $unidadesExtras = $cantidad - $unidadesIncluidas;
                            $subtotal = $unidadesExtras * $precioBase;
                            $contadorSopa += $cantidad;
                        } else {
                            $subtotal = $cantidad * $precioBase;
                        }
                    } elseif ($tipo === 'ENSALADAS') {
                        if ($esComboPerFecto) {
                            $unidadesIncluidas = max(0, min(1 - $contadorEnsaladas, $cantidad));
                            $unidadesExtras = $cantidad - $unidadesIncluidas;
                            $subtotal = $unidadesExtras * $precioBase;
                            $contadorEnsaladas += $cantidad;
                        } else {
                            $subtotal = $cantidad * $precioBase;
                        }
                    } else {
                        $subtotal = $cantidad * $precioBase;
                    }

                    $detalle->precio_unitario = $cantidad > 0 ? round($subtotal / $cantidad, 2) : $precioBase;
                    $detalle->subtotal = $subtotal;
                    if (!$detalle->numero_paquete) {
                        $detalle->numero_paquete = $numeroPaquete;
                    }
                    $detalle->save();
                }
            }

            $nuevoTotal = PedidoDetalle::where('id_pedido', $pedido->id)->sum('subtotal');
            Ticket::where('id_pedido', $pedido->id)->update(['total' => $nuevoTotal]);

            DB::commit();
            return redirect()->back()->with('success', 'Pedido actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al actualizar el pedido: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar un pedido y devolver el stock
     */
    public function cancelar($id)
    {
        DB::beginTransaction();
        try {
            $pedido = Pedido::with('detalles.comida')->findOrFail($id);

            // Devolver el stock de todos los productos del pedido
            foreach ($pedido->detalles as $detalle) {
                $comida = $detalle->comida;
                if ($comida) {
                    // Incrementar el stock disponible
                    $comida->increment('disponible', $detalle->cantidad);
                }
            }

            // Cancelar el ticket asociado si existe
            $ticket = Ticket::where('id_pedido', $pedido->id)->first();
            if ($ticket) {
                $tipoCancelado = TipoTicket::firstOrCreate(['nombre' => 'Cancelado']);
                $ticket->update(['id_tipo_ticket' => $tipoCancelado->id]);
            }

            // Marcar el pedido como cancelado
            $tipoCancelado = TipoPedido::firstOrCreate(['nombre' => 'Cancelado']);
            $pedido->update(['id_tipo_pedido' => $tipoCancelado->id]);

            DB::commit();
            return redirect()->back()->with('success', 'Pedido cancelado y stock devuelto correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al cancelar el pedido: ' . $e->getMessage());
        }
    }
}
