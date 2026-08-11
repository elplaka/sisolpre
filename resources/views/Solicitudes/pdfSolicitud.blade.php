<!DOCTYPE html>
<html>

<head>
    <title>Mi PDF</title>
    {!! $css !!}
</head>

<body>
    @php
    setlocale(LC_TIME, 'es_ES.UTF-8');
    $path = getcwd() . '/img/Marca_Gestion.jpg';
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $escudo = 'data:image/' . $type . ';base64,' . base64_encode($data);
    $domicilioSolicitante = false;
    $domicilioPropietario = false;

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

    if ($solicitud->propiedad) //Si el trámite lleva PROPIEDAD
    {
    $esSolicitante = !($solicitud->contacto->id != $solicitud->propiedad->contacto->id);
    }
    else //Si el trámite no lleva PROPIEDAD
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
            <td style="width: 40.5%;">{{ str_pad($solicitud->folio, 4, '0', STR_PAD_LEFT) }}</td>

            <td style="text-align: right; font-weight: bold; width: 26%;">Fecha:</td>
            <td style="width: 14%;">
                @php
                $fechaAceptacion = \Carbon\Carbon::parse($solicitud->fecha_aceptacion)->locale('es');
                $mes = $fechaAceptacion->translatedFormat('F'); // Get the month name, e.g., "Junio" or "Enero"
                $mesFormateado = mb_strtoupper($mes);
                @endphp
                {{ $fechaAceptacion->translatedFormat('d/') }}{{ $mesFormateado }}{{ $fechaAceptacion->translatedFormat('/Y') }}
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
            @if ($solicitud->propiedad->contacto->email && strlen(trim($solicitud->propiedad->contacto->email)) > 0)
            <td class="tdPrevFieldName" style="width: 13%;">E-mail:</td>
            <td style="width: 57%;">{{ $solicitud->propiedad->contacto->email }}</td>
            @endif
        </tr>
        @if (($solicitud->propiedad->contacto->domicilio_notificacion && strlen(trim($solicitud->propiedad->contacto->domicilio_notificacion->direccion)) > 0))
        @php
        $domicilioPropietario = true;
        @endphp
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 13%;">Domicilio:</td>
            <td colspan="3" style="width: 87%; line-height: 1; text-align: justify;">{{ $solicitud->propiedad->contacto->domicilio_notificacion->direccion }}</td>
        </tr>
        @endif
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
        <tr style="line-height: 1em; text-align: center;">
            <td colspan="4" style="text-align: center;">
                <span class="tdPrevFieldName" style="white-space: nowrap;">Organización / Razón Social:</span>
                <span>{{ $solicitud->razon_social->nombre }}</span>
            </td>
        </tr>
        @endif
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 10%;">CURP:</td>
            <td style="width: 10%;">{{ $solicitud->contacto->persona->curp }}</td>
            <td class="tdPrevFieldName" style="width: 13%;">Nombre:</td>
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
        @if ($solicitud->propiedad && $solicitud->contacto->id == $solicitud->propiedad->contacto->id)
        @if (($solicitud->propiedad->contacto->domicilio_notificacion && strlen(trim($solicitud->propiedad->contacto->domicilio_notificacion->direccion)) > 0))
        @php
        $domicilioSolicitante = true;
        @endphp
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 13%;">Domicilio:</td>
            <td colspan="3" style="width: 87%; text-align: justify;">{{ $solicitud->propiedad->contacto->domicilio_notificacion->direccion }}</td>
        </tr>
        @endif
        @else
        @if (($solicitud->contacto->domicilio_notificacion && strlen(trim($solicitud->contacto->domicilio_notificacion->direccion)) > 0))
        @php
        $domicilioSolicitante = true;
        @endphp
        <tr style="line-height: 1em;">
            <td class="tdPrevFieldName" style="width: 13%;">Domicilio:</td>
            <td colspan="3" style="width: 87%; text-align: justify;">{{ $solicitud->contacto->domicilio_notificacion->direccion }}</td>
        </tr>
        @endif
        @endif
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
                {{ $solicitud->propiedad->calle }}
                @else
                <span style="color: red;">«CALLE SIN CAPTURAR»</span>
                @endif
                @if ($solicitud->propiedad->numero)
                ,@php
                $numeroPropiedad = trim(strtoupper($solicitud->propiedad->numero));
                @endphp

                @if ($numeroPropiedad != 'S/N')
                &nbsp;N°
                @endif
                {{ $solicitud->propiedad->numero }}
                @else
                @if ($solicitud->tramites->where('tramite.id', '!=', 4)->count() > 0)
                <span style="color: red;">«NÚMERO SIN CAPTURAR»</span>
                @endif
                @endif
                @if ($solicitud->propiedad->colonia),@php
                $coloniaNombre = $solicitud->propiedad->colonia->nombre;
                // Define los prefijos que no deben mostrarse
                $prefixesToExclude = ['FRACC', 'INFONA', 'COL.', 'COLONIA', 'COL', 'COL,'];

                // Inicializa showColPrefix a true por defecto
                $showColPrefix = true;

                // Itera sobre los prefijos a excluir para ver si la colonia comienza con alguno de ellos
                foreach ($prefixesToExclude as $prefix) {
                if (str_starts_with($coloniaNombre, $prefix)) {
                $showColPrefix = false;
                break; // Sal del bucle una vez que encuentres una coincidencia
                }
                }
                @endphp

                @if ($showColPrefix)
                COL.
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
            <td class="tdPrevFieldName" style="width: 25%;">Información de Referencia:</td>
            <td style="width: 75%; padding-top: 7px;">{{ $solicitud->referencia->contenido }}</td>
        </tr>
        <tr style="line-height: 0.75em;">
            <td class="tdPrevFieldName" style="width: 15%; padding-bottom: 15px;">Localidad:</td>
            <td style="width: 40%; padding-bottom: 15px;">
                @if ($solicitud->referencia->localidad)
                {{ $solicitud->referencia->localidad->nombre }}
                @else
                - - - - -
                @endif
            </td>
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
                @if ($domicilioPropietario && $domicilioSolicitante)
                <div style="position: relative; width: auto; height: auto; border: 1px solid #ccc; display: inline-block; overflow: hidden; margin: 0 auto; padding: 0.2cm 0.2cm">
                    <div style="display: block;">
                        <img src="{{ $croquis }}" alt="Croquis de Localización"
                            style="width: auto; height: 5cm; display: block;">
                    </div>
                    <img src="{{ $norte }}" alt="Norte"
                        style="position: absolute; top: 0.1cm; right: 0.2cm; max-height: 1.5cm; width: 1.3cm;">
                </div>
                @else
                @if ($domicilioPropietario || $domicilioSolicitante)
                <div style="position: relative; width: auto; height: auto; border: 1px solid #ccc; display: inline-block; overflow: hidden; margin: 0 auto; padding: 0.2cm 0.2cm">
                    <div style="display: block;">
                        <img src="{{ $croquis }}" alt="Croquis de Localización"
                            style="width: auto; height: 5.5cm; display: block;">
                    </div>
                    <img src="{{ $norte }}" alt="Norte"
                        style="position: absolute; top: 0.2cm; right: 0.2cm; max-height: 1.75cm; width: 1.5cm;">
                </div>
                @else
                <div style="position: relative; width: auto; height: auto; border: 1px solid #ccc; display: inline-block; overflow: hidden; margin: 0 auto; padding: 0.2cm 0.2cm">
                    <div style="display: block;">
                        <img src="{{ $croquis }}" alt="Croquis de Localización"
                            style="width: auto;" class="img-croquis {{ $imagenAlta ? 'h-alta' : 'h-normal' }}">
                    </div>
                    <img src="{{ $norte }}" alt="Norte"
                        style="position: absolute; top: 0.2cm; right: 0.2cm; max-height: 2cm; width: 1.75cm;">
                </div>
                @endif
                @endif
                @endif
            </td>
        </tr>
        <tr><span style="display: block; margin-top: 0.25cm"></span></tr>
    </table>
    @php
    // 1. Filtrar la colección: Quedarse solo con los requisitos donde 'entregado' sea true.
    $requisitosEntregados = $requisitosConEstado->filter(function ($requisito) {
    return $requisito->entregado === true;
    });

    // 2. Agrupar la colección filtrada en bloques de 5 para las filas de la tabla.
    // 🔑 CAMBIO CLAVE: chunk(5)
    $gruposEntregados = $requisitosEntregados->chunk(5);
    @endphp

    @if($gruposEntregados->isNotEmpty())
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td class="titleTd" style="text-align: center;">
                DOCUMENTACIÓN ENTREGADA
            </td>
        </tr>
        <tr>
            <td style="height: 0.1cm;"></td>
        </tr>
        <tr>
            <td style="padding: 0; margin: 0; line-height: 1.0;">
                <table style="border-collapse: collapse; width: 100%;">
                    @forelse ($gruposEntregados as $grupo)
                    <tr>
                        @foreach ($grupo as $requisito)
                        <td style="vertical-align: top; font-size: 7pt; width: 20%; padding: 1px 2px; line-height: 1.0;">
                            • {{
                                        strlen($requisito->nombre_corto) > 20 
                                        ? \Illuminate\Support\Str::limit($requisito->nombre_corto, 20)
                                        : $requisito->nombre_corto
                                    }}
                        </td>
                        @endforeach
                        @php
                        $columnasFaltantes = 5 - $grupo->count();
                        @endphp

                        @if ($columnasFaltantes > 0)
                        <td colspan="{{ $columnasFaltantes }}"
                            style="width: {{ $columnasFaltantes * 20 }}%;">
                        </td>
                        @endif
                    </tr>
                    <!-- @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 10px;">
                                No se entregó ningún requisito.
                            </td>
                        </tr>
                    @endforelse -->
                </table>
            </td>
        </tr>
    </table>
    @endif
    @php
    $url = route('solicitudes.view', ['folio_digital' => $solicitud->folio_digital]);
    $qr = base64_encode(QrCode::format('svg')->size(250)->generate($url));
    @endphp
    <table style="position: fixed; bottom: 3.5cm; left: 0cm; right: 0cm; font-size: 8pt; text-align: right;">
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
                Bajo protesta de decir la verdad, manifiesto que los datos presentados en la presente son verdaderos, que el proyecto cumple con lo estipulado en el Reglamento de Construcciones para el municipio, así mismo, acepto las obligaciones y responsabilidades estipulados en el mismo y de las sanciones a las que estoy sujeto en el caso de su incumplimiento
            </span>
        </tr>
    </table>
    <div style="position: fixed; bottom: 0.1cm; left: 1cm; right: 0cm; font-size: 8pt; text-align: right; font-style: italic;">
        Fecha y hora de impresión: {{ now()->format('d/m/Y g:i a') }}
    </div>
</body>

</html>