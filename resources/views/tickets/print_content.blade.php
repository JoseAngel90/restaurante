@php
    // Ancho de línea de referencia (no añadimos espacios, el centrado lo hace ESC/POS)
    function line($c='-', $len=42) { return str_repeat($c, $len); }

    // Definiciones que faltaban
    $fecha = $ticket->fecha_ticket instanceof \Carbon\Carbon
        ? $ticket->fecha_ticket
        : \Carbon\Carbon::parse($ticket->fecha_ticket ?? now());

    $tipoPedido = strtolower(optional($ticket->pedido->tipoPedido)->nombre ?? '');
    $esVentaDirecta = stripos($ticket->pedido->notas ?? '', 'Venta directa') !== false;

    // Armar paquetes
    $paquetes = [];
    $currentPaquete = 0;
    if ($ticket->pedido && $ticket->pedido->detalles) {
        foreach ($ticket->pedido->detalles as $item) {
            $tipo = strtoupper(optional(optional($item->comida)->tipoComida)->descripcion ?? '');
            if ($tipo === 'PLATO FUERTE') { $currentPaquete++; }
            $paquetes[$currentPaquete][] = $item;
        }
    }
@endphp
{{ line('=') }}
LA OLLITA
Comida Casera
Tel: 123-456-7890
{{ line('=') }}

TICKET #{{ $ticket->id }}
{{ line() }}
Fecha: {{ $fecha->format('d/m/Y H:i') }}

Cliente: {{ optional(optional($ticket->pedido)->cliente)->nombre ?? 'N/A' }}
Tel: {{ optional(optional($ticket->pedido)->cliente)->telefono ?? 'N/A' }}

@if($tipoPedido === 'domicilio' || $tipoPedido === 'a domicilio')
** A DOMICILIO **
@elseif($esVentaDirecta)
** VENTA DIRECTA **
@else
** PARA LLEVAR **
@endif

{{ line('=') }}

@foreach($paquetes as $numPaquete => $items)
[ PAQUETE {{ $numPaquete }} ]
{{ line() }}
@foreach($items as $item)
@php
    $nombre = mb_substr(optional($item->comida)->nombre ?? 'Producto', 0, 28);
    $cant = $item->cantidad . 'x';
    $precio = '$' . number_format($item->subtotal ?? 0, 2);
    $fila = str_pad($nombre, 28) . ' ' . str_pad($cant, 4) . ' ' . str_pad($precio, 8, ' ', STR_PAD_LEFT);
@endphp
{{ $fila }}
@endforeach

@endforeach
{{ line('=') }}
TOTAL: ${{ number_format($ticket->total ?? 0, 2) }}
{{ line('=') }}

Metodo de pago: {{ optional($ticket->tipoPago)->nombre ?? 'N/A' }}
@if($ticket->tipoPago && strtolower($ticket->tipoPago->nombre) === 'efectivo')
Recibido: ${{ number_format($ticket->monto_recibido ?? 0, 2) }}
Cambio: ${{ number_format($ticket->cambio ?? 0, 2) }}
@endif

{{ line() }}
¡Gracias por su compra!
Vuelva pronto
{{ line() }}

