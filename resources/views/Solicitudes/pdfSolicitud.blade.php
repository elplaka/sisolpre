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
        $path = getcwd() . '/img/Marca_Gestion.jpg';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $escudo = 'data:image/' . $type . ';base64,' . base64_encode($data);

        if ($solicitud->propiedad)
        {
            $imgCroquis = $solicitud->propiedad->img_croquis; // El nombre de tu archivo de imagen
        }
        else 
        {
            $imgCroquis = $solicitud->croquis_aux->img; // El nombre de tu archivo de imagen
        }

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

        if ($solicitud->propiedad)  //Si el trámite lleva PROPIEDAD
        { 
            $esSolicitante = !($solicitud->contacto->id != $solicitud->propiedad->contacto->id);
        }
        else   //Si el trámite no lleva PROPIEDAD
        {
            $esSolicitante = 1;
        }

    @endphp
    <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
        <tr>
            <td style="text-align: center !important; padding-bottom: 0.2cm; margin: 0;">
                <img src="{{ $escudo }}" alt="Escudo"
                    style="max-width: 19cm; height: auto; margin: 0 0 0 0 !important; padding: 0; opacity: 0.85;">
            </td>
        </tr>
        <tr>
            <td style="text-align: center !important; padding: 0; margin: 0;">
                <h2 style="color: #333333; line-height: 0.8; margin: 0; padding: 0;">
                    <span style="letter-spacing: 0.05em;">DIRECCIÓN DE PLANEACIÓN URBANA</span>
                </h2>
                <h1 style="color: #333333; line-height: 0.8; margin: 0; padding: 0;">FORMATO ÚNICO DE SOLICITUD</h1>
            </td>
        </tr>
    </table>
    <table style="width: 100%; border-collapse: collapse; font-size: 12pt; margin: 0; padding: 0;">
        <tr style="line-height: 1">
            <td style="text-align: left; font-weight: bold; width: 3.5%; margin: 0; padding: 0; height:0.8cm">Folio:</td>
            <td style="width: 40.5%;">{{ str_pad($solicitud->id, 4, '0', STR_PAD_LEFT) }}</td>

            <td style="text-align: right; font-weight: bold; width: 26%;">Fecha de Ingreso:</td>
            <td style="width: 14%;">
                @php
                    $fechaIngreso = \Carbon\Carbon::parse($solicitud->fecha_ingreso)->locale('es');
                    $mes = $fechaIngreso->translatedFormat('F'); // Get the month name, e.g., "Junio" or "Enero"
                    $mesFormateado = mb_strtoupper($mes);
                @endphp
                {{ $fechaIngreso->translatedFormat('d/') }}{{ $mesFormateado }}{{ $fechaIngreso->translatedFormat('/Y') }}
            </td>
        </tr>
    </table>
    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
        @if (!$esSolicitante)
        <tr>
            <td class="titleTd" colspan="4" style="width: 100%;">
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
        <tr>
            <td class="titleTd" colspan="4" style="width: 100%;">
                @if ($solicitud->propiedad && $solicitud->contacto->id == $solicitud->propiedad->contacto->id)
                    SOLICITANTE / PROPIETARIO
                @else
                    SOLICITANTE
                @endif
            </td>
        </tr>
        @if ($solicitud->razon_social)
        <tr style="line-height: 1em;">
            <td colspan="2" class="tdPrevFieldName" style="white-space: nowrap;">Organización / Razón Social:</td>
            <td colspan="2">{{ $solicitud->razon_social->nombre }}</td>
        </tr>
        @endif
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
    </table>
    @if ($solicitud->propiedad)
    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
        <tr>
            <td class="titleTd" colspan="4" style="width: 100%;">
                PROPIEDAD
            </td>
        </tr>
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 15%;">Cve. Catastral:</td>
            <td style="width: 10%;">{{ $solicitud->propiedad->clave_catastral }}</td>
            <td class="tdPrevFieldName" style="width: 30%;">Tipo:</td>
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
                @endif
            </td>
        </tr>
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 15%;">Localidad:</td>
            <td style="width: 40%;"> 
                @if ($solicitud->propiedad->localidad)
                    {{ $solicitud->propiedad->localidad->nombre }}
                @else
                    <span style="color: red;">«SIN SELECCIONAR»</span>
                @endif
            </td>
        </tr>
        <tr><span style="display: block; margin-top: 0.25cm"></span></tr>
    </table>
    @endif
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
            <td class="titleTd" colspan="{{ $numeroTiposTramite }}" style="text-align: center;">
                TRÁMITE{{ $solicitud->tramites->count() > 1 ? 'S' : ''}} A REALIZAR
            </td>
        </tr>
        <tr style="line-height: 1em;">
            {{-- Encabezados de Tipo de Trámite --}}
            @foreach ($tramitesAgrupados as $tipoTramiteNombre => $tramitesDeEsteTipo)
                <th style="text-align: left; padding: 0;">
                    <div style="display: inline-block; width: 95%; border-bottom: 1px solid #ccc;">
                        {{ $tipoTramiteNombre }}
                    </div>
                </th>
            @endforeach
        </tr>
        <tr style="width:100%; line-height: 1em;">
            {{-- Listas de Trámites --}}
            @foreach ($tramitesAgrupados as $tipoTramiteNombre => $tramitesDeEsteTipo)
                <td style="vertical-align: top; text-align:left; padding: 2px 8px;"> {{-- Specific padding for td --}}
                    <ul style="list-style-type: disc; padding-left: 0; margin: 0 0 0 0.1cm; line-height: 1em;"> {{-- Adjusted margin and added line-height --}}
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
            {{-- <td class="tdPrevFieldName" style="width: 20%;">Sector:</td>
            <td style="width: 30%;"> 
                @if ($solicitud->sectorTramite)
                    {{ $solicitud->sectorTramite->nombre }}
                @else
                    <span style="color: red;">«SIN SELECCIONAR»</span>
                @endif
            </td> --}}
        </tr>
        <tr><span style="display: block; margin-top: 0.2cm"></span></tr>
    </table>
    @if ($solicitud->referencia)
        <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
            <tr>
                <td class="titleTd" colspan="4" style="width: 100%;">     
                REFERENCIA
                </td>
            </tr>
            <tr style="line-height: 0.75em;">
                <td class="tdPrevFieldName" style="width: 25%; padding-bottom: 10px;">Información de Referencia:</td>
                <td style="width: 75%; padding-top: 7px; padding-bottom: 15px;">{{ $solicitud->referencia->contenido }}</td>
            </tr>
        </table>
    @endif
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td class="titleTd" colspan="{{ $numeroTiposTramite }}" style="text-align: center; ">
                CROQUIS DE LOCALIZACIÓN
            </td>
        </tr>
        @if ($croquis != '')
        <tr>
            <td colspan="{{ $numeroTiposTramite }}" style="height: 0.2cm;"></td>
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
                            style="width: auto; height: 6.5cm; display: block;">
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
        <tr class="titlePrevTr">
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
    @php
        $url = route('solicitudes.view', ['folio_digital' => $solicitud->folio_digital]);
        $qr = base64_encode(QrCode::format('svg')->size(250)->generate($url));
    @endphp
    <table style="position: fixed; bottom: 4cm; left: 0cm; right: 0cm; font-size: 8pt; text-align: right;">
        <tr>
            <table style="width:100%;">
                <tr>
                    <!-- Columna izquierda: QR -->
                    <td style="width:2.5cm; vertical-align:top; padding-right:10px;">
                        <img style="width:2cm; opacity:0.8;" src="data:image/png;base64,{{ $qr }}">
                    </td>

                    <!-- Columna derecha: texto alineado con QR -->
                    <td style="width:auto;">
                        <span style="display:block; text-align:center; padding:0; margin:0; font-weight:800; line-height:1;">
                            NOMBRE Y FIRMA DE QUIEN PRESENTA LA DOCUMENTACIÓN
                        </span>
                        <span style="display:block; text-align:center; padding:0; margin:0; line-height:1;">
                            Estoy consciente de los requisitos y ACEPTO DE CONFORMIDAD
                        </span>
                        <span style="width:75%; display:block; border-bottom:1px solid black; margin:0 auto 4px auto; height:1cm;"></span>
                    </td>
                </tr>
            </table>

            <!-- Texto legal debajo de toda la tabla -->
            <span style="display:block; text-align:justify; font-size:8pt; line-height:0.75; margin-top:4px;">
                Bajo protesta de decir la verdad, manifestando que los datos presentados en la solicitud son verdaderos y por lo tanto me hago sabedor(a) de las penas que incurra por falsedad en términos del Código Penal para el estado libre y soberano de Sinaloa. Asimismo, si presento documentación falsa esta solicitud deberá acompañarse por lo dispuesto en el Reglamento de Construcciones para el municipio de Concordia, Sinaloa, y/o la Ley de Ordenamiento Territorial y Desarrollo Urbano del Estado de Sinaloa.
            </span>
        </tr>
    </table>
    <div style="position: fixed; bottom: 0.1cm; left: 1cm; right: 0cm; font-size: 8pt; text-align: right; font-style: italic;">
        Fecha y hora de impresión: {{ now()->format('d/m/Y g:i a') }}  
    </div>
</body>
</html>