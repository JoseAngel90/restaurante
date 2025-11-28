<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Semanal - La Ollita</title>
    <style>
        @page {
            margin: 30px 40px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            background-color: #fbf5ef;
            color: #4e3b2c;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }

        .contenedor {
            border: 2px solid #d4bfa5;
            padding: 25px 40px;
            border-radius: 8px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            font-family: 'Georgia', serif;
            font-size: 36px;
            font-style: italic;
            font-weight: bold;
        }

        .subtitulo {
            font-size: 13px;
            margin-top: -5px;
            color: #6c584c;
        }

        .horario {
            font-size: 14px;
            margin-top: 8px;
            font-weight: bold;
        }

        .rango {
            display: inline-block;
            background-color: #4e3b2c;
            color: #fff;
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 13px;
            margin: 10px 0 20px 0;
        }

        .dias-container {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 25px;
    }

        .dia {
        flex: 1 1 calc(20% - 10px); /* 5 días por fila */
        min-width: 180px;
        background-color: #fffdf9;
        border: 1px solid #e5d8ca;
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        box-sizing: border-box;
        box-shadow: 0 2px 4px rgba(150, 120, 90, 0.1);
    }

         .dia h3 {
        font-size: 16px;
        color: #4e3b2c;
        border-bottom: 1px solid #d6c3b2;
        padding-bottom: 5px;
        margin-bottom: 8px;
    }

        ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
        text-align: left;
    }

        ul li {
        font-size: 12.5px;
        margin-bottom: 4px;
        line-height: 1.4;
    }

    

        .footer {
            text-align: center;
            border-top: 1px solid #d6c3b2;
            padding-top: 10px;
            font-size: 12px;
            color: #6c584c;
            margin-top: 25px;
        }

        .precio {
            font-weight: bold;
            font-size: 13px;
            margin-top: 5px;
        }

        .contacto {
            margin-top: 8px;
            font-size: 12.5px;
        }

        .iconos {
            font-size: 12px;
        }

        @media print {
        .dia {
            page-break-inside: avoid;
        }
    }
    </style>
</head>
<body>
    <div class="contenedor">
        <div class="header">
            <div class="logo">La Ollita</div>
            <div class="subtitulo">Comida para llevar</div>
            <div class="horario">Lunes a Viernes de 1pm a 4pm</div>

            @if(count($diasSemana))
                <div class="rango">
                    Menú del {{ $diasSemana[0]['fecha'] }} al {{ end($diasSemana)['fecha'] }}
                </div>
            @endif
        </div>

        <div class="dias-container">
    @foreach($diasSemana as $dia)
        <div class="dia">
            <h3>{{ $dia['nombre'] }}</h3>
            @if($dia['comidas']->count())
                <ul>
                    @foreach($dia['comidas'] as $comida)
                        <li>{{ $comida->nombre }}</li>
                    @endforeach
                </ul>
            @else
                <p><em>No hay comidas registradas para este día.</em></p>
            @endif
        </div>
    @endforeach
</div>

        <div class="footer">
            <div class="precio">Precio de paquete individual: <strong>$95</strong> (no olvide sus recipientes)</div>
            <div>“Por favor, traiga sus recipientes y bolsas reutilizables.”</div>
            <div><strong>Cuidemos el planeta 🌎</strong></div>

            <div class="contacto">
                <div>📞 246 109 53 61</div>
                <div>@la_ollita_comida_</div>
            </div>
        </div>
    </div>
</body>
</html>
