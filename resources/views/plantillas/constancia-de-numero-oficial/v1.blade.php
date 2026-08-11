<style>
    /* Cambia este valor (0.8, 1.2, 1.5, etc.) para ajustar todo el documento */
    :root {
        --interlineado: 0.95;
    }

    .documento-cuerpo {
        line-height: var(--interlineado);
        font-family: 'FigTree', sans-serif;
    }

    /* Clase específica para los párrafos de texto */
    .parrafo-justificado {
        text-align: justify;
        padding: 0 20px;
        line-height: var(--interlineado);
    }

    /* Para asegurar que las tablas mantengan un ritmo visual pero no se rompan */
    .tabla-datos {
        line-height: 1;
        /* Las cajas grises suelen verse mejor compactas */
    }
</style>

<div class="documento-cuerpo">
    <div style="padding: 20px;">
        <table style="line-height: 0.75; width: 100%; border-collapse: collapse; margin-bottom:20px">
            <tr>
                <td style="text-align: center;">
                    <h2 style="font-weight: 800; font-size: 14pt; text-transform: uppercase; margin: 0;">
                        DIRECCIÓN DE PLANEACIÓN URBANA
                    </h2>
                    <h3 style="font-weight: 800; margin: 5px 0 0 0; font-size: 12pt;">
                        Oficio N° {{ $data['prefijo_oficio'] }}{{ $data['consecutivo_oficio'] }}
                    </h3>
                    <h3 style="font-weight: 800; margin: 5px 0 0 0; font-size: 12pt;">
                        ASUNTO: ASIGNACIÓN DE NÚMERO OFICIAL
                    </h3>
                </td>
            </tr>
        </table>
    </div>

    <div style="font-size:12pt; line-height: 0.75; padding: 0 20px; margin-bottom: 18px;">
        <div style="font-weight: 700; text-transform: uppercase;">C. {{ $data['nombre_propietario'] }}</div>
        <div style="font-weight: 700; margin-top: 2px;">{{ $data['texto_propietario'] }}</div>
    </div>

    <div class="parrafo-justificado" style="margin-bottom:15px">
        En atención a su solicitud y la documentación presentada, y con fundamento en
        la Ley de Ordenamiento Territorial y Desarrollo Urbano para el Estado de Sinaloa, y las
        demás disposiciones reglamentarias vigentes en el municipio; se hace constar
        que el predio ubicado en:
    </div>
    <div class="bloque-domicilio" style="line-height: 1.2; margin: 10px 20px; text-align: center;">
        <div style="background-color: #f0f0f0; border: 1px solid #e0e0e0; border-radius: 4px; padding: 6px 0;">
            <table class="tabla-datos" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: right; width: 15%; vertical-align: middle;">
                        <span style="color: #555; font-weight: 600; text-transform: uppercase; font-size: 10pt;">DOMICILIO</span>
                    </td>
                    <td style="text-align: center; width: 5%; color: #bbb; font-size: 14pt;">|</td>
                    <td style="text-align: justify; width: 80%; vertical-align: middle; padding-right: 15px;">
                        <span class="texto-principal" style="font-weight: 700; font-size: 10pt; line-height: 1.0; display: block;">
                            {{ $data['domicilio_propiedad'] }}, {{ $data['localidad_propiedad'] }}, MUNICIPIO DE CONCORDIA, SINALOA
                        </span>

                        <span style="font-weight: 500; font-size: 8pt; color: #555; display: block; margin-top: 1px;">
                            {{ $data['referencias_ubicacion'] }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div style="line-height: 0.75; margin: 15px 20px; text-align: center;">
        <div style="background-color: #f0f0f0; border: 1px solid #e0e0e0; border-radius: 4px; padding: 4px 0;">
            <table class="tabla-datos" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: right; width: 38%; vertical-align: middle;">
                        <span style="color: #555; font-weight: 600; text-transform: uppercase; font-size: 10pt;">Clave Catastral</span>
                    </td>
                    <td style="text-align: center; width: 8%; color: #bbb; font-size: 14pt;">|</td>
                    <td style="text-align: left; width: 54%; vertical-align: middle;">
                        <span style="font-weight: 800; font-size: 13pt; letter-spacing: 1.5pt;">{{ $data['clave_catastral'] }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="parrafo-justificado" style="margin-bottom:10px">
        Se le ha asignado el
    </div>

    <div style="margin: 10px 20px; text-align: center;">
        <div style="background-color: #f0f0f0; border: 1px solid #e0e0e0; border-radius: 4px; padding: 4px 0;">
            <table class="tabla-datos" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: right; width: 20%;">
                        <span style="color: #555; font-weight: 600; text-transform: uppercase; font-size: 9pt;">NÚMERO OFICIAL</span>
                    </td>
                    <td style="text-align: center; width: 6%; color: #bbb;">|</td>
                    <td style="text-align: center; width: 10%;">
                        <span style="font-weight: 800; font-size: 11pt;">{{ $data['numero_asignado'] }}</span>
                    </td>
                    <td style="text-align: center; width: 6%; color: #bbb;">|</td>
                    <td style="text-align: center; width: 58%;">
                        <span style="font-weight: 800; font-size: 10pt; text-transform: uppercase;">{{ $data['numero_asignado_letra'] }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div style="margin: 15px 20px; text-align: center;">
        <div style="background-color: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 4px; padding: 4px 0;">
            <table class="tabla-datos" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: center; width: 30%; vertical-align: middle;">
                        <span style="color: #555; font-weight: 600; text-transform: uppercase; font-size: 9pt;">REFERENCIA SIGM (UTM)</span>
                    </td>
                    <td style="text-align: center; width: 5%; color: #ccc; font-size: 12pt;">|</td>
                    <td style="text-align: left; width: 65%; vertical-align: middle;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 50%; text-align: center; font-weight: 700; font-size: 10pt; color: #444; vertical-align: middle;">
                                    X: <span style="font-size: 11pt; margin-left: 2px; letter-spacing: 0.5pt">{{ $data['coordenada_utm_x'] }}</span>
                                </td>

                                <td style="width: 1px; border-left: 1px solid #ddd; height: 12px;"></td>

                                <td style="width: 50%; text-align: center; font-weight: 700; font-size: 10pt; color: #444; vertical-align: middle;">
                                    Y: <span style="font-size: 11pt; margin-left: 2px; letter-spacing: 0.5pt">{{ $data['coordenada_utm_y'] }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="parrafo-justificado" style="margin-bottom:10px">
        Mismo que deberá utlizarse en toda identificación, trámite y referencia administrativa
        o legal relacionada al predio, y el cual deberá colocarse en la parte visible de la entrada y ser legible por lo menos a 20 metros de distancia.
    </div>

    <div class="parrafo-justificado" style="margin-bottom:10px">
        Esta asignación es definitiva y sólo podrá modificarse por la actualización de los planes y programas
        de desarrollo urbano municipales.
    </div>

    <div class="parrafo-justificado" style="margin-bottom: 30px;">
        Se expide para los fines y usos que {{ $data['texto_interesado'] }} convengan.
    </div>

    <table style="line-height: 0.9; width: 100%; border-collapse: collapse; margin-bottom:18px; margin-top: 20px">
        <tr>
            <td style="text-align: center;">
                <div style="font-weight: 700; text-align: center; letter-spacing: 2pt;">
                    ATENTAMENTE
                </div>
                <div style="font-weight: 700; text-align: center;">
                    Heroica Ciudad Concordia; a {{ $data['fecha_emision'] }}
                </div>
                <div style="font-style:italic; font-weight: 700; text-align: center;">
                    "{{ $data['slogan'] }}"
                </div>
            </td>
        </tr>
    </table>
    <table style="width: 100%; margin-top: 60px; margin-bottom:15px">
        <tr>
            <td style="text-align: center;">
                <div style="width: 250px; border-top: 1px solid #000; margin: 0 auto 5px auto;"></div>
                <strong style="text-transform: uppercase;">{{ $data['autoridad_firmante'] }}</strong><br>
                <strong><span> {{ $data['cargo'] }} </span></strong>
            </td>
        </tr>
    </table>
    <div style="font-size:6pt; line-height: 0.75; padding: 0 20px; margin-bottom: 20px;">
        <div style="font-weight: 600;">c.c.p. TESORERÍA - Edificio</div>
        <div style="font-weight: 600; margin-top: 2px;">c.c.p. ARCHIVO </div>
    </div>
</div>