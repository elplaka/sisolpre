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
        $path = getcwd() . '/img/escudo.jpg';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $escudo = 'data:image/' . $type . ';base64,' . base64_encode($data);

        $imgCroquis = $solicitud->propiedad->img_croquis; // El nombre de tu archivo de imagen
        $path = 'public/croquis/' . $imgCroquis; // La ruta relativa dentro de la carpeta 'storage'
        // Verifica si el archivo existe antes de intentar leerlo
        if (Storage::exists($path) && $imgCroquis) {
            // Obtener el contenido del archivo
            $data = Storage::get($path);
            // Obtener el tipo MIME de la imagen (ej. 'image/jpeg', 'image/png')
            // Esto es más robusto que solo la extensión
            $type = Storage::mimeType($path);

            // Codificar la imagen en base64
            $croquis = 'data:' . $type . ';base64,' . base64_encode($data); 
        } else {
            // Manejar el caso donde la imagen no se encuentra
            // Puedes asignar una imagen por defecto, un placeholder, o un string vacío.
            $croquis = ''; // O la ruta a una imagen de "no encontrado" en base64
            // Log::warning("El archivo de croquis no se encontró en: " . $path);
        }

        $path = getcwd() . '/img/norte.png';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $norte = 'data:image/' . $type . ';base64,' . base64_encode($data);

        $esSolicitante = !($solicitud->contacto->id != $solicitud->propiedad->contacto->id);
    @endphp
    <table>
        <tr>
            <td style="width:22%;  text-align:right !important">
                <img src="{{ $escudo }}" alt="Escudo" style="max-width: 1.5cm; height: auto; margin-right:0.5cm !important; opacity: 0.85">
            </td>
            <td style="width:78%; text-align:left !important">
                <h3 style="color: #333333; line-height: 1; margin-bottom:0; text-align:left !important;">
                    H. Ayuntamiento de Concordia |
                    Gobierno Municipal {{ $periodo->años }} <br>
                <span style="letter-spacing: 0.15em; text-align:left !important;">DIRECCIÓN DE PLANEACIÓN MUNICIPAL URBANA</span>  
                </h3>
                <h1 style="color: #333333; line-height: 1; margin-top:0; text-align:left !important;">REGISTRO PRELIMINAR DE SOLICITUD</h1>
            </td>
        </tr>
     </table>    
    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
        <tr>
            <td style="text-align: left; font-weight: bold; width: 5%;">Folio:</td>
            <td style="width: 40%;">{{ str_pad($solicitud->id, 4, '0', STR_PAD_LEFT) }}</td>

            <td style="text-align: right; font-weight: bold; width: 26%; height:1.25cm">Fecha de Ingreso:</td>
            <td style="width: 14%;">
                @php
                    $fechaIngreso = \Carbon\Carbon::parse($solicitud->fecha_ingreso)->locale('es');
                    $mes = $fechaIngreso->translatedFormat('F'); // Get the month name, e.g., "Junio" or "Enero"
                    $mesFormateado = mb_strtoupper($mes); // Convert to uppercase for June
                @endphp
                {{ $fechaIngreso->translatedFormat('d/') }}{{ $mesFormateado }}{{ $fechaIngreso->translatedFormat('/Y') }}
            </td>
        </tr>
    </table>
    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
        <tr>
            <td class="titlePrevTd" colspan="4" style="width: 100%;">
                @if ($solicitud->contacto->id == $solicitud->propiedad->contacto->id)
                    SOLICITANTE / PROPIETARIO
                @else
                    SOLICITANTE
                @endif
            </td>
        </tr>
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 10%;">CURP:</td>
            <td style="width: 10%;">{{ $solicitud->contacto->persona->curp }}</td>
            <td class="tdPrevFieldName"  style="width: 13%;">Nombre:</td>
            <td style="width: 57%;">{{ $solicitud->contacto->persona->nombre . ' ' .  $solicitud->contacto->persona->apellidos }}</td>
        </tr>
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 10%;">Teléfono:</td>
            <td style="width: 10%;">
                @if ($solicitud->contacto->telefono && strlen(trim($solicitud->contacto->telefono)) > 0)
                    {{ $solicitud->contacto->telefono }}
                @else
                    <span style="color: red;">«SIN CAPTURAR»</span>
                @endif
            </td>
            @if ($solicitud->contacto->email && strlen(trim($solicitud->contacto->email)) > 0)
                <td class="tdPrevFieldName" style="width: 13%;">E-mail:</td>
                <td style="width: 57%;">
                    {{ $solicitud->contacto->email }}
                </td>
            @endif
        </tr>
        <tr><span style="display: block; margin-top: 0.25cm"></span></tr>
        @if (!$esSolicitante)
            <tr>
                <td class="titlePrevTd" colspan="4" style="width: 100%;">
                    PROPIETARIO
                </td>
            </tr>
            <tr style="line-height: 1em;">
                <td class="tdPrevFieldName" style="width: 10%;">CURP:</td>
                <td style="width: 10%;">{{ $solicitud->propiedad->contacto->persona->curp }}</td>
                <td class="tdPrevFieldName" style="width: 13%;">Nombre:</td>
                <td style="width: 57%;">{{ $solicitud->propiedad->contacto->persona->nombre . ' ' .  $solicitud->propiedad->contacto->persona->apellidos }}</td>
            </tr>
            <tr style="line-height: 1em;">
                <td class="tdPrevFieldName" style="width: 10%;">Teléfono:</td>
                <td style="width: 10%;">
                    @if ($solicitud->propiedad->contacto->telefono && strlen(trim($solicitud->propiedad->contacto->telefono)) > 0)
                        {{ $solicitud->propiedad->contacto->telefono }}
                    @else
                        <span style="color: red;">«SIN CAPTURAR»</span>
                    @endif
                </td>
                <td class="tdPrevFieldName" style="width: 13%;">E-mail:</td>
                <td style="width: 57%;">{{ $solicitud->propiedad->contacto->email }}</td>
            </tr>
            <tr><span style="display: block; margin-top: 0.25cm"></span></tr>
        @endif
    </table>
    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
        <tr>
            <td class="titlePrevTd" colspan="4" style="width: 100%;">
                PROPIEDAD
            </td>
        </tr>
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 15%;">Cve. Catastral:</td>
            <td style="width: 10%;">{{ $solicitud->propiedad->clave_catastral }}</td>
            <td class="tdPrevFieldName"  style="width: 30%;">Tipo:</td>
            <td style="width: 40%;">
                @if ($solicitud->propiedad->tipo === null)
                    <span style="color: red;">«SIN SELECCIONAR»</span>
                @else
                    {{ $solicitud->propiedad->tipo->nombre }}
                @endif
            </td>
        </tr>
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 15%;">Superficie:</td>
            <td style="width: 10%;"> 
                @if ($solicitud->propiedad->superficie && $solicitud->propiedad->superficie > 0)
                    {{ number_format($solicitud->propiedad->superficie, fmod($solicitud->propiedad->superficie, 1) !== 0.0 ? 2 : 0) }} m<sup style="font-size:7pt">2</sup>
                @else
                    <span style="color: red;">«SIN CAPTURAR»</span>
                @endif
                </td>
            @if ($solicitud->propiedad->id_tipo == 2)
            <td class="tdPrevFieldName" style="width: 30%;">Sup. en Construcción:</td>
            <td style="width: 40%;"> 
                @if ($solicitud->propiedad->superficie_construccion && $solicitud->propiedad->superficie_construccion >= 0)
                    {{ number_format($solicitud->propiedad->superficie_construccion, fmod($solicitud->propiedad->superficie_construccion, 1) !== 0.0 ? 2 : 0) }} m<sup style="font-size:7pt">2</sup>
                @else
                    <span style="color: red;">«SIN CAPTURAR»</span>
                @endif
            </td>
            @endif
        </tr>
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 15%;">Domicilio:</td>
            <td colspan="3" style="width: 85%;">
                @if ($solicitud->propiedad->calle)
                    {{ $solicitud->propiedad->calle }},
                @else
                    <span style="color: red;">«CALLE SIN CAPTURAR»</span>
                @endif
                @if ($solicitud->propiedad->numero)
                    &nbsp; N° 
                    {{ $solicitud->propiedad->numero }},
                @else
                    <span style="color: red;">«NÚMERO SIN CAPTURAR»</span>
                @endif 
                @if ($solicitud->propiedad->colonia)
                   @php
                        $coloniaNombre = $solicitud->propiedad->colonia->nombre;
                        $showColPrefix = !str_starts_with($coloniaNombre, 'FRACC') && !str_starts_with($coloniaNombre, 'INFONA');
                    @endphp

                    @if ($showColPrefix)
                        &nbsp; COL. 
                    @endif
                    {{ $coloniaNombre }}
                {{-- @else
                    «SIN COLONIA» --}}
                @endif
            </td>
        </tr>
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 15%;">Localidad:</td>
            <td style="width: 10%;"> 
                @if ($solicitud->propiedad->localidad)
                    {{ $solicitud->propiedad->localidad->nombre }}
                @else
                    <span style="color: red;">«SIN SELECCIONAR»</span>
                @endif
            </td>
        </tr>
        <tr><span style="display: block; margin-top: 0.25cm"></span></tr>
    </table>
    @php
       // Obtener los trámites agrupados directamente del accesor
        $tramitesAgrupados = $solicitud->grouped_tramites;

        // Calcular el número de tipos de trámite
        $numeroTiposTramite = $tramitesAgrupados->count();

        // Calcular el ancho de cada celda
        $anchoCelda = $numeroTiposTramite > 0 ? (100 / $numeroTiposTramite) : 100;
    @endphp
    <table style="table-layout: auto;">
        <tr>
            <td class="titlePrevTd" colspan="{{ $numeroTiposTramite }}" style="text-align: center;">
                TRÁMITE{{ $solicitud->tramites->count() > 1 ? 'S' : ''}} A REALIZAR
            </td>
        </tr>
        <tr>
            {{-- Encabezados de Tipo de Trámite --}}
            @foreach ($tramitesAgrupados as $tipoTramiteNombre => $tramitesDeEsteTipo)
                <th style="text-align:left; padding: 2px 8px;"> {{-- Specific padding for th --}}
                    {{ $tipoTramiteNombre }}
                </th>
            @endforeach
        </tr>
        <tr style="width:100%">
            {{-- Listas de Trámites --}}
            @foreach ($tramitesAgrupados as $tipoTramiteNombre => $tramitesDeEsteTipo)
                <td style="vertical-align: top; text-align:left; padding: 2px 8px;"> {{-- Specific padding for td --}}
                    <ul style="list-style-type: disc; padding-left: 0; margin: 0 0 0 0.3cm; line-height: 1.2em;"> {{-- Adjusted margin and added line-height --}}
                        @foreach ($tramitesDeEsteTipo as $tramite)
                            <li>{{ $tramite['tramite_nombre'] }}</li>
                        @endforeach
                    </ul>
                </td>
            @endforeach
        </tr>
    </table>
    <table>
        <tr>
            <td class="tdPrevFieldName" style="width: 20%;">Destino de la Obra:</td>
            <td style="width: 80%;"> 
                @if ($solicitud->destino_obra)
                    {{ $solicitud->destino_obra->nombre }}
                @else
                    <span style="color: red;">«SIN SELECCIONAR»</span>
                @endif
            </td>
        </tr>
        <tr><span style="display: block; margin-top: 0.25cm"></span></tr>
    </table>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td class="titlePrevTd" colspan="{{ $numeroTiposTramite }}" style="text-align: center; ">
                CROQUIS DE LOCALIZACIÓN
            </td>
        </tr>
                
        @if ($croquis != '')
        <tr>
            <td colspan="{{ $numeroTiposTramite }}" style="height: 0.25cm;"></td>
        </tr>
        @endif

        <tr>
            <td colspan="{{ $numeroTiposTramite }}" style="text-align: center; margin-top:0.25cm">
                @if ($croquis == '')
                    <span style="color: red; margin: 0 0; padding: 0.25cm 0.25cm">«SIN IMAGEN DE CROQUIS»</span>
                @else
                <div style="position: relative; width: auto; height: auto; border: 1px solid #ccc; display: inline-block; overflow: hidden; margin: 0 auto; padding: 0.2cm 0.2cm">
                    <div style="display: block;">
                        <img src="{{ $croquis }}" alt="Croquis de Localización"
                            style="width: auto; height: 7.5cm; display: block;">
                    </div>
                    <img src="{{ $norte }}" alt="Norte"
                        style="position: absolute; top: 0.3cm; right: 0.4cm; max-height: 2cm; width: 1.75cm;">
                </div>
                @endif
            </td>
        </tr>
        <tr><span style="display: block; margin-top: 0.25cm"></span></tr>
    </table>
    {{-- <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td class="titlePrevTd" style="text-align: center; ">
                DOCUMENTACIÓN
            </td>
        </tr>
        <tr>
            <td style="padding: 0; margin: 0;">
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="vertical-align: middle; padding-right: 0.5cm; width: 2%">
                            <div class="cuadro-checkbox"> ✓
                            </div>
                        </td>
                        <td style="vertical-align: middle; padding-right: 1cm;">
                            <span style="position: relative; top: -3px;">INE</span>
                        </td>
                        <td style="vertical-align: middle; padding-right: 0.5cm; width: 2%">
                             <div class="cuadro-checkbox"> ✗
                            </div>
                        </td>
                        <td style="vertical-align: middle; padding-right: 1cm;">
                            <span style="position: relative; top: -3px;">Predial</span>
                        </td>
                              <td style="vertical-align: middle; padding-right: 0.5cm; width: 2%">
                             <div class="cuadro-checkbox">
                            </div>
                        </td>
                        <td style="vertical-align: middle; padding-right: 1cm;">
                            <span style="position: relative; top: -3px; color: red">«Escrituras»</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table> --}}
    <div style="position: fixed; bottom: 0.5cm; left: 1cm; right: 0cm; font-size: 8pt; text-align: right;">
        Fecha y hora de impresión: {{ now()->format('d/m/Y g:i a') }}  
    </div>
</body>
</html>