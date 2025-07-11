<!DOCTYPE html>
<html>
<head>
    <title>Solicitud</title>
</head>
<body>
    @php
        $norte = asset('img/norte.png');
        $esSolicitante = !($solicitud->contacto->id != $solicitud->propiedad->contacto->id);
    @endphp
    <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0; font-family: Arial, sans-serif;">
        <tbody>
            <tr>
                <td style="text-align: center; padding-bottom: 5px; margin: 0;">
                    <img src="{{ $message->embed(public_path('img/Logo_Y_Escudo.jpg')) }}" alt="Logo y Escudo" style="height: 100px;">
                </td>
            </tr>
            <tr>
                <td style="text-align: center; padding: 0; margin: 0;">
                    <h2 style="color: #333333; line-height: 1.2; margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 14pt; font-weight: 600;">
                        <span>DIRECCIÓN DE PLANEACIÓN MUNICIPAL URBANA</span>
                    </h2>
                    <h1 style="color: #333333; line-height: 1.2; margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 15pt; font-weight: 800;">FORMATO ÚNICO DE SOLICITUD</h1>
                </td>
            </tr>
        </tbody>
    </table>
    <br>
    <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
        <tbody>
            <tr>
                <td style="padding-left: 20px; padding-right: 20px; text-align: center;">
                    <table style="width: 800px; max-width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e5e7eb; border-radius: 18px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); margin: 0 auto; overflow: hidden;">
                        <tbody>
                            <tr>
                                <td style="padding: 24px; font-family: Arial, sans-serif; border-radius: 18px;">
                                    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
                                        <tbody>
                                            <tr>
                                                <td style="text-align: left; font-weight: bold; width: 3.5%; padding-right: 5px; vertical-align: top;">Folio:</td>
                                                <td style="width: 40.5%; vertical-align: top; text-align: left;">{{ str_pad($solicitud->id, 4, '0', STR_PAD_LEFT) }}</td>
                                            </tr>
                                            <tr>
                                                <td style="text-align: left; font-weight: bold; width: 26%; padding-left: 10px; vertical-align: top;">Fecha de Ingreso:</td>
                                                <td style="width: 14%; vertical-align: top;">
                                                    @php
                                                        $fechaIngreso = \Carbon\Carbon::parse($solicitud->fecha_ingreso)->locale('es');
                                                        $mes = $fechaIngreso->translatedFormat('F');
                                                        $mesFormateado = mb_strtoupper($mes);
                                                    @endphp
                                                    {{ $fechaIngreso->translatedFormat('d/') }}{{ $mesFormateado }}{{ $fechaIngreso->translatedFormat('/Y') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <br>
    <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
        <tbody>
            <tr>
                <td style="padding-left: 20px; padding-right: 20px; text-align: center;">
                    <table style="width: 800px; max-width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e5e7eb; border-radius: 18px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); margin: 0 auto; overflow: hidden;">
                        <tbody>
                            <tr>
                                <td colspan="2" style="width: 100%; letter-spacing: 0.1em; font-weight: bold; text-align: center; background-color: #dadada; padding: 8px 0; line-height: 1.2; font-family: Arial, sans-serif;">
                                    @if ($solicitud->contacto->id == $solicitud->propiedad->contacto->id)
                                        SOLICITANTE / PROPIETARIO
                                    @else
                                        SOLICITANTE
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 0 24px 4px 24px; font-family: Arial, sans-serif;">
                                    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
                                        <tbody>
                                            <tr>
                                                <td style="width: 10%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">CURP:</td>
                                                <td style="width: 90%; padding: 4px 0; text-align: left;">{{ $solicitud->contacto->persona->curp }}</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 13%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">Nombre:</td>
                                                <td style="width: 87%; padding: 4px 0; text-align: left;">{{ $solicitud->contacto->persona->nombre . ' ' . $solicitud->contacto->persona->apellidos }}</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 10%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">Teléfono:</td>
                                                <td style="width: 90%; padding: 4px 0; text-align: left;">
                                                    @if ($solicitud->contacto->telefono && strlen(trim($solicitud->contacto->telefono)) > 0)
                                                        {{ $solicitud->contacto->telefono }}
                                                    @else
                                                        <span style="color: red;">&laquo;SIN CAPTURAR&raquo;</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if ($solicitud->contacto->email && strlen(trim($solicitud->contacto->email)) > 0)
                                                <tr>
                                                    <td style="width: 13%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">E-mail:</td>
                                                    <td style="width: 87%; padding: 4px 0; text-align: left;">
                                                        {{ $solicitud->contacto->email }}
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <br>
    @if (!$esSolicitante)
    <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
        <tbody>
            <tr>
                <td style="padding-left: 20px; padding-right: 20px; text-align: center;">
                    <table style="width: 800px; max-width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e5e7eb; border-radius: 18px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); margin: 0 auto; overflow: hidden;">
                        <tbody>
                            <tr>
                                <td colspan="2" style="width: 100%; letter-spacing: 0.1em; font-weight: bold; text-align: center; background-color: #dadada; padding: 8px 0; line-height: 1.2; font-family: Arial, sans-serif;">
                                    PROPIETARIO
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 0 24px 4px 24px; font-family: Arial, sans-serif;">
                                    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
                                        <tbody>
                                            <tr>
                                                <td style="width: 10%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">CURP:</td>
                                                <td style="width: 90%; padding: 4px 0;">{{ $solicitud->propiedad->contacto->persona->curp }}</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 13%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">Nombre:</td>
                                                <td style="width: 87%; padding: 4px 0; text-align: left;">{{ $solicitud->propiedad->contacto->persona->nombre . ' ' . $solicitud->propiedad->contacto->persona->apellidos }}</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 10%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">Teléfono:</td>
                                                <td style="width: 90%; padding: 4px 0; text-align: left;">
                                                    @if ($solicitud->propiedad->contacto->telefono && strlen(trim($solicitud->propiedad->contacto->telefono)) > 0)
                                                        {{ $solicitud->propiedad->contacto->telefono }}
                                                    @else
                                                        <span style="color: red;">&laquo;SIN CAPTURAR&raquo;</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 13%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">E-mail:</td>
                                                <td style="90%; padding: 4px 0; text-align: left;">{{ $solicitud->propiedad->contacto->email }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    @endif
    <br>
    <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
        <tbody>
            <tr>
                <td style="padding-left: 20px; padding-right: 20px; text-align: center;">
                    <table style="width: 800px; max-width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e5e7eb; border-radius: 18px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); margin: 0 auto; overflow: hidden;">
                        <tbody>
                            <tr>
                                <td colspan="2" style="width: 100%; letter-spacing: 0.1em; font-weight: bold; text-align: center; background-color: #dadada; padding: 8px 0; line-height: 1.2; font-family: Arial, sans-serif;">
                                    PROPIEDAD
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 0 24px 4px 24px; font-family: Arial, sans-serif;">
                                    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
                                        <tbody>
                                            <tr>
                                                <td style="width: 18%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">Cve. Catastral:</td>
                                                <td style="width: 82%; padding: 4px 0; text-align: left;">{{ $solicitud->propiedad->clave_catastral }}</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">Tipo:</td>
                                                <td style="width: 70%; padding: 4px 0; text-align: left;">
                                                    @if ($solicitud->propiedad->tipo === null)
                                                        <span style="color: red;">&laquo;SIN SELECCIONAR&raquo;</span>
                                                    @else
                                                        {{ $solicitud->propiedad->tipo->nombre }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 18%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">Superficie:</td>
                                                <td style="width: 82%; padding: 4px 0; text-align: left;">
                                                    @if ($solicitud->propiedad->superficie && $solicitud->propiedad->superficie > 0)
                                                        {{ number_format($solicitud->propiedad->superficie, fmod($solicitud->propiedad->superficie, 1) !== 0.0 ? 2 : 0) }} m<sup style="font-size:7pt">2</sup>
                                                    @else
                                                        <span style="color: red;">&laquo;SIN CAPTURAR&raquo;</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if ($solicitud->propiedad->id_tipo == 2)
                                                <tr>
                                                    <td style="width: 30%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">Sup. en Construcción:</td>
                                                    <td style="width: 70%; padding: 4px 0; text-align: left;">
                                                        @if ($solicitud->propiedad->superficie_construccion && $solicitud->propiedad->superficie_construccion >= 0)
                                                            {{ number_format($solicitud->propiedad->superficie_construccion, fmod($solicitud->propiedad->superficie_construccion, 1) !== 0.0 ? 2 : 0) }} m<sup style="font-size:7pt">2</sup>
                                                        @else
                                                            <span style="color: red;">&laquo;SIN CAPTURAR&raquo;</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td style="width: 18%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">Domicilio:</td>
                                                <td style="width: 82%; padding: 4px 0; text-align: left;">
                                                    @if ($solicitud->propiedad->calle)
                                                        {{ $solicitud->propiedad->calle }},
                                                    @else
                                                        <span style="color: red;">&laquo;CALLE SIN CAPTURAR&raquo;</span>
                                                    @endif
                                                    @if ($solicitud->propiedad->numero)
                                                        &nbsp; N°
                                                        {{ $solicitud->propiedad->numero }}
                                                    @else
                                                        <span style="color: red;">&laquo;NÚMERO SIN CAPTURAR&raquo;</span>
                                                    @endif
                                                    @if ($solicitud->propiedad->colonia)
                                                        @php
                                                            $coloniaNombre = $solicitud->propiedad->colonia->nombre;
                                                            $showColPrefix = !str_starts_with($coloniaNombre, 'FRACC') && !str_starts_with($coloniaNombre, 'INFONA');
                                                        @endphp
                                                        ,
                                                        @if ($showColPrefix)
                                                            &nbsp; COL.
                                                        @endif
                                                        {{ $coloniaNombre }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 18%; text-align: right; font-weight: bold; padding: 4px 5px 4px 0;">Localidad:</td>
                                                <td style="width: 82%; padding: 4px 0; text-align: left;">
                                                    @if ($solicitud->propiedad->localidad)
                                                        {{ $solicitud->propiedad->localidad->nombre }}
                                                    @else
                                                        <span style="color: red;">&laquo;SIN SELECCIONAR&raquo;</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    @php
       // Obtener los trámites agrupados directamente del accesor
        $tramitesAgrupados = $solicitud->grouped_tramites;

        // Calcular el número de tipos de trámite
        $numeroTiposTramite = $tramitesAgrupados->count();

        // Calcular el ancho de cada celda
        $anchoCelda = $numeroTiposTramite > 0 ? (100 / $numeroTiposTramite) : 100;
    @endphp
    <br>
    <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
        <tbody>
            <tr>
                <td style="padding-left: 20px; padding-right: 20px; text-align: center;">
                    <table style="width: 800px; max-width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e5e7eb; border-radius: 18px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); margin: 0 auto; overflow: hidden;">
                        <tbody>
                            <tr>
                                <td colspan="4" style="width: 100%; letter-spacing: 0.1em; font-weight: bold; text-align: center; background-color: #dadada; padding: 8px 0; line-height: 1.2; font-family: Arial, sans-serif;">
                                    TRÁMITE{{ $solicitud->tramites->count() > 1 ? 'S' : ''}} A REALIZAR
                                </td>
                            </tr>
                            <tr>
                                <td colspan="1" style="padding: 0 24px 8px 24px; font-family: Arial, sans-serif;">
                                    <table style="width: 100%; border-collapse: collapse; font-size: 12pt;">
                                        <tbody>
                                            @foreach ($tramitesAgrupados as $tipoTramiteNombre => $tramitesDeEsteTipo)
                                                <tr>
                                                    <td style="padding-top: 10px;">
                                                        <div style="font-weight: bold; padding-bottom: 5px; border-bottom: 1px solid #ccc;">
                                                            {{ $tipoTramiteNombre }}
                                                        </div>
                                                        <ul style="list-style-type: disc; padding-left: 20px; margin: 5px 0 0 0; line-height: 1.5;">
                                                            @foreach ($tramitesDeEsteTipo as $tramite)
                                                                <li>{{ $tramite['tramite_nombre'] }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <br>
    <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
        <tbody>
            <tr>
                <td style="padding-left: 20px; padding-right: 20px; text-align: center;">
                    <table style="width: 800px; max-width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e5e7eb; border-radius: 18px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); margin: 0 auto; overflow: hidden;">
                        <tbody>
                            <tr>
                                <td colspan="2" style="width: 100%; letter-spacing: 0.1em; font-weight: bold; text-align: center; background-color: #dadada; padding: 8px 0; line-height: 1.2; font-family: Arial, sans-serif;">
                                    DESTINO DE LA OBRA
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 4px 24px 4px 24px; font-family: Arial, sans-serif; font-size: 12pt;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tbody>
                                            <tr>
                                                <td style="width: 100%; padding: 4px 0; text-align: center;">
                                                    @if ($solicitud->destino_obra)
                                                        {{ $solicitud->destino_obra->nombre }}
                                                    @else
                                                        <span style="color: red;">&laquo;SIN SELECCIONAR&raquo;</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <br>
    <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
        <tbody>
            <tr>
                <td style="padding-left: 20px; padding-right: 20px; text-align: center;">
                    <table style="width: 800px; max-width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e5e7eb; border-radius: 18px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); margin: 0 auto; overflow: hidden;">
                        <tbody>
                            <tr>
                                 <td colspan="2" style="width: 100%; letter-spacing: 0.1em; font-weight: bold; text-align: center; background-color: #dadada; padding: 8px 0; line-height: 1.2; font-family: Arial, sans-serif;">
                                    CROQUIS DE LOCALIZACIÓN
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 0 24px 24px 24px; font-family: Arial, sans-serif; font-size: 12pt; text-align: center;">
                                   <div style="display: inline-block; border: 1px solid #ccc; margin-top: 10px; padding: 0; vertical-align: top;">
                                        <table cellpadding="0" cellspacing="0" style="width: 100%;">
                                            <tr>
                                                <td style="padding: 0; position: relative;">
                                                    <!-- Tabla contenedora -->
                                                    <table cellpadding="0" cellspacing="0" style="width: 100%;">
                                                        <tr>
                                                            <td>
                                                                <!-- Imagen principal -->
                                                                <img src="{{ $message->embed(storage_path('app/public/croquis/' . $solicitud->propiedad->img_croquis)) }}"
                                                                    alt="Croquis"
                                                                    style="display: block; width: 100%; max-height: 6.5cm;">
                                                            </td>
                                                            <td style="width: 1.8cm; vertical-align: top; padding: 5px 5px 0 0;">
                                                                <!-- Imagen norte alineada arriba a la derecha -->
                                                                <img src="{{ $message->embed(public_path('img/norte.png')) }}"
                                                                    alt="Norte"
                                                                    style="width: 1.8cm; height: auto;">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <br>
    <!-- Párrafo introductorio -->
    <p style="font-family: Arial, sans-serif; font-size: 10pt; margin: 20px 0; text-align: center;">
        Tu solicitud ha sido registrada exitosamente en el <strong>Sistema de Planeación Municipal Urbana</strong>.  
        En la SOLICITUD IMPRESA encontrarás un <strong>código QR</strong> que te permitirá que te permitirá acceder a la información relacionada.  
        Para lo cual será necesario contar con el siguiente token de acceso:
    </p>

    <!-- Tabla con token centrado -->
    <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
        <tr>
            <td colspan="4" style="font-family: Arial, sans-serif; padding: 10px 20px; text-align: center;">
                <div style="font-weight: bold; margin-bottom: 10px;">TOKEN DE ACCESO</div>
                <span style="
                    background-color: #7b003a;
                    color: white;
                    font-size: 14pt;
                    font-weight: bold;
                    padding: 10px 20px;
                    border-radius: 8px;
                    display: inline-block;
                    margin-top: 5px;">
                    {{ $solicitud->token_acceso }}
                </span>
            </td>
        </tr>
    </table>
</body>
</html>