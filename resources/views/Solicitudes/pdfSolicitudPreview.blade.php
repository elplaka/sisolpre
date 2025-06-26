<!DOCTYPE html>
<html>
<head>
    <title>Mi PDF</title>
   <style>
        {!! $css !!}
    </style>
</head>
<body>
    @php
        setlocale(LC_TIME, 'es_ES.UTF-8');
    @endphp
    <h3 style="line-height: 1; margin-bottom:0">
        H. Ayuntamiento de Concordia ::
        Gobierno Municipal {{ $periodo->años }} <br>
       <span style="letter-spacing: 0.225em;">- DIRECCIÓN DE PLANEACIÓN MUNICIPAL -</span>  
    </h3>

    <h2 style="line-height: 1; margin-top:0">REGISTRO PRELIMINAR DE SOLICITUD</h2>
    
    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
        <tr>
            <td style="text-align: left; font-weight: bold; width: 5%;">Folio:</td>
            <td style="width: 40%;">{{ str_pad($solicitud->id, 4, '0', STR_PAD_LEFT) }}</td>

            <td style="text-align: right; font-weight: bold; width: 25%; height:1.25cm">Fecha de ingreso:</td>
            <td style="width: 15%;">
                @php
                    $fechaIngreso = \Carbon\Carbon::parse($solicitud->fecha_ingreso)->locale('es');
                    $mes = $fechaIngreso->translatedFormat('F'); // Get the month name, e.g., "Junio" or "Enero"
                    if ($fechaIngreso->month === 6) { // Carbon's month property for June is 6
                        $mesFormateado = mb_strtoupper($mes); // Convert to uppercase for June
                    } else {
                        $mesFormateado = $mes; // Keep standard capitalization for other months
                    }
                @endphp
                {{ $fechaIngreso->translatedFormat('d/') }}{{ $mesFormateado }}{{ $fechaIngreso->translatedFormat('/Y') }}
            </td>
        </tr>
    </table>
    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
        <tr class="titlePrevTr">
            <td class="titlePrevTd" colspan="4" style="width: 100%;">
                @if ($solicitud->contacto->id == $solicitud->propiedad->contacto->id)
                    SOLICITANTE / PROPIETARIO
                @else
                    SOLICITANTE
                @endif
            </td>
        </tr>
        <tr>
            <td style="text-align: right; font-weight: bold; width: 10%;">CURP:</td>
            <td style="width: 10%;">{{ $solicitud->contacto->persona->curp }}</td>
            <td style="text-align: right; font-weight: bold; width: 15%;">Nombre:</td>
            <td style="width: 55%;">{{ $solicitud->contacto->persona->nombre . ' ' .  $solicitud->contacto->persona->apellidos }}</td>
        </tr>
        <tr>
            <td style="text-align: right; font-weight: bold; width: 10%;">Teléfono:</td>
            <td style="width: 10%;">{{ $solicitud->contacto->telefono }}</td>
            <td style="text-align: right; font-weight: bold; width: 15%;">E-mail:</td>
            <td style="width: 55%;">{{ $solicitud->contacto->email }}</td>
        </tr>
    </table>
  

</body>
</html>