<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConstanciaNumeroOficial;
use App\Models\ConstanciaNumeroOficialPlantilla;
use App\Models\JustificacionTramite;
use App\Models\AutoridadFirmante;
use App\Models\EstatusTramite;
use App\Models\Tramite;
use App\Models\Solicitud;
use App\Models\Propiedad;
use App\Models\Periodo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class ConstanciaNumeroOficialController extends Controller
{
    public function obtenerPlantillaActiva()
    {
        $plantilla = ConstanciaNumeroOficialPlantilla::where('activa', true)->first();

        return $plantilla;
    }

    public function obtenerAutoridadFirmanteActiva()
    {
        $autoridadFirmante = AutoridadFirmante::where('activa', true)->first();

        return $autoridadFirmante;
    }

    private function registrarCambio($modelo, $idTramitePrincipal, $accion)
    {
        // getDirty() detecta qué campos cambiaron comparado con la base de datos
        $cambios = $modelo->getDirty();

        if (!empty($cambios)) {
            $antes = array_intersect_key($modelo->getOriginal(), $cambios);

            $modelo->historial()->create([
                'id_tramite_principal' => $idTramitePrincipal,
                'id_usuario'           => Auth::id(),
                'accion'               => $accion,
                'valores_anteriores'   => $antes,
                'valores_nuevos'       => $cambios,
            ]);
        }
    }

    public function agregaJustificacion($idTramite, $motivo)
    {
        $motivoUpper = Str::upper(trim($motivo));
        $justificacion = JustificacionTramite::where('id_tramite', $idTramite)->first();

        if ($justificacion) {
            $justificacion->fill(['motivo' => $motivoUpper]);
            $this->registrarCambio($justificacion, $idTramite, 'ACTUALIZAR_JUSTIFICACION');
            $justificacion->save();
        } else {
            $justificacion = JustificacionTramite::create([
                'id_tramite' => $idTramite,
                'motivo'     => $motivoUpper
            ]);

            $justificacion->historial()->create([
                'id_tramite_principal' => $idTramite,
                'id_usuario'           => Auth::id(),
                'accion'               => 'CREAR_JUSTIFICACION',
                'valores_anteriores'   => null,
                'valores_nuevos'       => ['motivo' => $motivoUpper],
            ]);
        }

        return $justificacion;
    }

    public function validaTramite($request)
    {
        $anoOficio = Carbon::parse($request->input('fecha_emision'))->year;

        $idActual = $request->id_constancia; // Ajusta esto según cómo recibas el ID del oficio

        $request->merge([
            'consecutivo' => (int) $request->consecutivo
        ]);

        $validator = Validator::make($request->all(), [
            'id_tramite'            => 'required|exists:tramites,id',
            'fecha_emision'         => 'required|date',
            'consecutivo'           => 'required|integer',
            'prefijo_base'          => 'required|string|max:25',
            'consecutivo'           => [
                'required',
                'integer',
                'min:1',
                Rule::unique('constancias_numero_oficial', 'consecutivo_oficio')
                    ->where(fn($query) => $query->where('ano_oficio', $anoOficio))
                    ->when($idActual, function ($rule, $idActual) {
                        return $rule->ignore($idActual);
                    })
            ],
            'prefijo_base'          => 'required|string|max:25',
            'numero_asignado'       => 'required|string|max:10',
            'numero_asignado_letra' => 'required|string|max:60',
            'numero_asignado'       => 'required|string|max:10',
            'numero_asignado_letra' => 'required|string|max:60',
            'id_estatus'            => 'required|integer',
            'motivo_justificacion'  => 'required_if:id_estatus,3,4,5|nullable|string|min:10',
        ], [
            'id_tramite.required'            => 'El ID del trámite es inválido o se perdió la referencia.',
            'fecha_emision.required'         => 'La FECHA DE EMISIÓN es obligatoria.',
            'consecutivo.required'           => 'El número CONSECUTIVO de OFICIO es obligatorio.',
            'prefijo_base.required'         => 'El PREFIJO del NÚMERO DE OFICIO es obligatorio.',
            'consecutivo.required'          => 'El número CONSECUTIVO de OFICIO es obligatorio.',
            'consecutivo.integer'           => 'El número CONSECUTIVO de OFICIO debe ser numérico.',
            'consecutivo.min'               => 'El número CONSECUTIVO de OFICIO no debe estar vacío.',
            'consecutivo.unique'            => "El número de oficio {$request->consecutivo} ya existe para el año {$anoOficio}.",
            'prefijo_base.max'               => 'El PREFIJO es demasiado largo (máximo 25 caracteres).',
            'numero_asignado.max'            => 'El NÚMERO ASIGNADO no debe exceder los 10 caracteres.',
            'numero_asignado_letra.max'      => 'El NÚMERO EN LETRA es demasiado largo (máximo 60 caracteres).',
            'numero_asignado.required'       => 'El NÚMERO OFICIAL ASIGNADO es obligatorio.',
            'numero_asignado_letra.required' => 'El NÚMERO EN LETRA es obligatorio.',
            'id_estatus.required'            => 'Debes seleccionar un ESTATUS para el trámite.',
            'motivo_justificacion.required_if' => 'Debes escribir un MOTIVO DE JUSTIFICACIÓN para este estatus.',
            'motivo_justificacion.min'        => 'El MOTIVO debe ser más descriptivo (mínimo 10 caracteres).',
        ]);

        // Si es una petición de Inertia/Axios, puedes retornar los errores así:
        return $validator;
    }

    public function validarConsecutivo(Request $request)
    {
        $request->validate([
            'consecutivo' => 'required',
            'anio' => 'required',
        ]);

        $anio = date('Y', strtotime($request->anio));

        $existe = ConstanciaNumeroOficial::where('consecutivo_oficio', $request->consecutivo)
            ->where('ano_oficio', $anio)
            ->when($request->id_constancia, function ($query) use ($request) {
                $query->where('id', '!=', $request->id_constancia);
            })
            ->exists();

        return response()->json([
            'existe' => $existe
        ]);
    }

    private function procesarConstancia(Request $request, $constancia = null)
    {
        DB::beginTransaction();
        try {
            $idTramite = $request->input('id_tramite');
            $tramite = Tramite::findOrFail($idTramite);
            $propiedad = $request->input('propiedad');

            // --- 1. HISTORIAL DEL TRÁMITE (Estatus) ---
            $tramite->fill(['id_estatus' => $request->input('id_estatus')]);
            $this->registrarCambio($tramite, $idTramite, 'ACTUALIZAR_ESTATUS_TRAMITE');
            $tramite->save();

            // --- 2. JUSTIFICACIÓN ---
            $motivo = $request->input('motivo_justificacion');
            if ($motivo) {
                $this->agregaJustificacion($idTramite, $motivo);
            } else {
                // Registrar si se eliminó una justificación previa
                $existente = JustificacionTramite::where('id_tramite', $idTramite)->first();
                if ($existente) {
                    $existente->historial()->create([
                        'id_tramite_principal' => $idTramite,
                        'id_usuario'           => Auth::id(),
                        'accion'               => 'ELIMINAR_JUSTIFICACION',
                        'valores_anteriores'   => ['motivo' => $existente->motivo],
                        'valores_nuevos'       => null
                    ]);
                    $existente->delete();
                }
            }

            // --- 3. DATOS DE LA CONSTANCIA ---
            $fechaEmision = $request->input('fecha_emision');
            $dataConstancia = [
                'id_tramite'            => $idTramite,
                'ano_oficio'            => Carbon::parse($fechaEmision)->year,
                'prefijo_oficio'        => $request->input('prefijo_base'),
                'consecutivo_oficio'    => $request->input('consecutivo'),
                'id_plantilla'          => $this->obtenerPlantillaActiva()->id,
                'id_propiedad'          => $request->input('propiedad')['id'],
                'fecha_emision'         => $fechaEmision,
                'fecha_expiracion'      => Carbon::parse($fechaEmision)->addYear(),
                'numero_asignado'       => $request->input('numero_asignado'),
                'numero_asignado_letra' => Str::upper($request->input('numero_asignado_letra')),
                'id_usuario'            => Auth::id(),
                'id_autoridad_firmante' => $this->obtenerAutoridadFirmanteActiva()->id,
            ];

            if ($constancia) {
                $constancia->fill($dataConstancia);
                $this->registrarCambio($constancia, $idTramite, 'ACTUALIZAR_CONSTANCIA');
                $constancia->save();
            } else {
                $constancia = ConstanciaNumeroOficial::create($dataConstancia);
                $constancia->historial()->create([
                    'id_tramite_principal' => $idTramite,
                    'id_usuario'           => Auth::id(),
                    'accion'               => 'CREAR_CONSTANCIA',
                    'valores_anteriores'   => null,
                    'valores_nuevos'       => $constancia->only(['numero_asignado', 'consecutivo_oficio']),
                ]);

                // 3. Lógica de Propiedad (Solo si el número asignado cambió o es nueva)
                $propiedadOriginal = Propiedad::findOrFail($propiedad['id']);


                // if (is_null($propiedadOriginal->numero) || $propiedadOriginal->numero !== $request->input('numero_asignado')) {
                //     $nuevaPropiedad = $propiedadOriginal->replicate();
                //     $nuevaPropiedad->numero = $request->input('numero_asignado');
                //     if (is_null($propiedadOriginal->codigo_postal)) {
                //         $nuevaPropiedad->codigo_postal = $request->input('codigo_postal');
                //     }
                //     if (is_null($propiedadOriginal->coordenada_utm_x)) {
                //         $nuevaPropiedad->coordenada_utm_x = $request->input('coordenada_utm_x');
                //     }
                //     if (is_null($propiedadOriginal->coordenada_utm_y)) {
                //         $nuevaPropiedad->coordenada_utm_y = $request->input('coordenada_utm_y');
                //     }
                //     if (is_null($propiedadOriginal->referencias_ubicacion)) {
                //         $nuevaPropiedad->referencias_ubicacion = trim(mb_strtoupper($request->input('referencias_ubicacion')));
                //     }
                //     $nuevaPropiedad->save();

                //     Propiedad::where('clave_catastral', trim($propiedad['clave_catastral']))
                //         ->where('id', '!=', $nuevaPropiedad->id)
                //         ->update(['activa' => 0, 'editable' => 0]);

                //     $constancia->update(['id_propiedad' => $nuevaPropiedad->id]);
                // }

                if (
                    is_null($propiedadOriginal->numero) ||
                    $propiedadOriginal->numero != $request->input('numero_asignado') ||

                    is_null($propiedadOriginal->codigo_postal) ||
                    $propiedadOriginal->codigo_postal != $request->input('codigo_postal') ||

                    is_null($propiedadOriginal->coordenada_utm_x) ||
                    $propiedadOriginal->coordenada_utm_x != $request->input('coordenada_utm_x') ||

                    is_null($propiedadOriginal->coordenada_utm_y) ||
                    $propiedadOriginal->coordenada_utm_y != $request->input('coordenada_utm_y') ||

                    is_null($propiedadOriginal->referencias_ubicacion) ||
                    $propiedadOriginal->referencias_ubicacion != $request->input('referencias_ubicacion')
                ) {
                    $nuevaPropiedad = $propiedadOriginal->replicate();

                    if (
                        is_null($propiedadOriginal->numero) ||
                        $propiedadOriginal->numero != $request->input('numero_asignado')
                    ) {
                        $nuevaPropiedad->numero = $request->input('numero_asignado');
                    }

                    if (
                        is_null($propiedadOriginal->codigo_postal) ||
                        $propiedadOriginal->codigo_postal != $request->input('codigo_postal')
                    ) {
                        $nuevaPropiedad->codigo_postal = $request->input('codigo_postal');
                    }

                    if (
                        is_null($propiedadOriginal->coordenada_utm_x) ||
                        $propiedadOriginal->coordenada_utm_x != $request->input('coordenada_utm_x')
                    ) {
                        $nuevaPropiedad->coordenada_utm_x = $request->input('coordenada_utm_x');
                    }

                    if (
                        is_null($propiedadOriginal->coordenada_utm_y) ||
                        $propiedadOriginal->coordenada_utm_y != $request->input('coordenada_utm_y')
                    ) {
                        $nuevaPropiedad->coordenada_utm_y = $request->input('coordenada_utm_y');
                    }

                    if (
                        is_null($propiedadOriginal->referencias_ubicacion) ||
                        $propiedadOriginal->referencias_ubicacion != $request->input('referencias_ubicacion')
                    ) {
                        $nuevaPropiedad->referencias_ubicacion = $request->input('referencias_ubicacion');
                    }

                    $nuevaPropiedad->save();

                    Propiedad::where(
                        'clave_catastral',
                        trim($propiedad['clave_catastral'])
                    )
                        ->where('id', '!=', $nuevaPropiedad->id)
                        ->update([
                            'activa' => 0,
                            'editable' => 0
                        ]);

                    $constancia->update([
                        'id_propiedad' => $nuevaPropiedad->id
                    ]);
                }
            }

            // --- 5. SINCRONIZACIÓN DE DOCUMENTOS CON HISTORIAL ---
            $antesSync = [];
            $tramite->load('documentos');
            foreach ($tramite->documentos as $doc) {
                $antesSync[$doc->pivot->id_requisito_documentacion] = $doc->pivot->entregado ? 1 : 0;
            }

            // Cargamos los requisitos con su relación al catálogo maestro para obtener nombres
            $requisitosColeccion = $tramite->solicitud->documentos()->with('requisitoDocumentacion')->get();
            $dataSync = [];
            $documentosEstadoNuevos = [];

            foreach ($requisitosColeccion as $docReq) {
                $id = $docReq->id_requisito_documentacion;
                $estaEntregado = in_array($id, $request->documentacion_entregada ?? []);

                $dataSync[$id] = [
                    'entregado' => $estaEntregado,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
                $documentosEstadoNuevos[$id] = $estaEntregado ? 1 : 0;
            }

            ksort($antesSync);
            ksort($documentosEstadoNuevos);

            // Solo registramos si hay cambios reales en los checks
            if ($antesSync !== $documentosEstadoNuevos) {
                $tramite->documentos()->sync($dataSync);

                $lecturaAnterior = [];
                $lecturaNueva = [];

                foreach ($requisitosColeccion as $docReq) {
                    $id = $docReq->id_requisito_documentacion;
                    $nombre = $docReq->requisitoDocumentacion->nombre ?? "Documento ID: $id";

                    $lecturaAnterior[$nombre] = ($antesSync[$id] ?? 0) ? 'ENTREGADO' : 'PENDIENTE';
                    $lecturaNueva[$nombre] = ($documentosEstadoNuevos[$id] ?? 0) ? 'ENTREGADO' : 'PENDIENTE';
                }

                $tramite->historial()->create([
                    'id_tramite_principal' => $idTramite,
                    'id_usuario'           => Auth::id(),
                    'accion'               => 'ACTUALIZAR_DOCUMENTACION',
                    'valores_anteriores'   => $lecturaAnterior,
                    'valores_nuevos'       => $lecturaNueva,
                ]);
            } else {
                $tramite->documentos()->sync($dataSync);
            }

            DB::commit();
            return $tramite;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en Constancia: " . $e->getMessage());
            throw $e;
        }
    }

    public function store(Request $request)
    {
        $validator = $this->validaTramite($request);
        if ($validator->fails()) return back()->withErrors($validator);

        // if ($request->id_estatus == 2 && !$request->has('confirmacion_final')) {
        //     return back()->with([
        //         'necesitaConfirmacion' => true,
        //     ]);
        // }

        $tramite = $this->procesarConstancia($request);

        return redirect()->route('tramites')
            ->with([
                'success' => 'Confirmación de datos del Folio N° ' . str_pad($tramite->id, 4, '0', STR_PAD_LEFT) . ' realizada exitosamente',
                'idConstancia' => $tramite->constanciaNumeroOficial->id
            ]);
    }

    public function pdf(Request $request)
    {
        Log::info('1. Inicio PDF');

        $constancia = ConstanciaNumeroOficial::where('id_tramite', $request->id)->firstOrFail();

        Log::info('2. Constancia encontrada');

        $plantilla = ConstanciaNumeroOficialPlantilla::where('activa', true)->firstOrFail();

        Log::info('3. Plantilla encontrada');

        $nombreVista = "plantillas.constancia-de-numero-oficial.v{$plantilla->id}";

        Log::info('4. Antes de generar PDF');

        $curp = $constancia->propiedad?->contacto?->persona?->curp;

        $caracterSexo = Str::of($curp)->substr(10, 1)->upper()->toString();

        if ($caracterSexo === 'H') {
            $texto_propietario = 'Propietario';
        } elseif ($caracterSexo === 'M') {
            $texto_propietario = 'Propietaria';
        } else {
            $texto_propietario = 'Propietario(a)';
        }

        if ($caracterSexo === 'H') {
            $texto_interesado = 'al interesado';
        } elseif ($caracterSexo === 'M') {
            $texto_interesado = 'a la interesada';
        } else {
            $texto_interesado = 'al(a) interesado(a)';
        }

        $claveLimpia = $constancia->propiedad->clave_catastral;

        // Dividimos el string en un array de 3 en 3 y luego los unimos con un espacio
        $claveFormateada = implode(' ', str_split($claveLimpia, 3));

        $calle = $constancia->propiedad?->calle;
        $coloniaOriginal = $constancia->propiedad?->colonia?->nombre;
        $coloniaProcesada = null;

        $fecha_emision = $constancia->fecha_emision->locale('es')->translatedFormat('d \d\e F \d\e Y');

        if ($coloniaOriginal) {
            // Definimos los prefijos que NO queremos duplicar (Insensible a mayúsculas/minúsculas)
            // Buscamos: Col, Colonia, Inf, Infonavit, Fovisste (con o sin punto)
            $patron = '/^(col.|col|colonia|inf|inf.|infonavit|fovisste)\.?\s+/i';

            if (preg_match($patron, $coloniaOriginal)) {
                // Si ya trae el prefijo, lo dejamos tal cual
                $coloniaProcesada = $coloniaOriginal;
            } else {
                // Si no lo trae, le concatenamos "Colonia "
                $coloniaProcesada = "COLONIA " . $coloniaOriginal;
            }
        }

        $codigo_postal = $constancia->propiedad?->codigo_postal;

        $cpProcesado = null;
        // Asumimos que $codigoPostal viene de $constancia->propiedad->codigo_postal
        if (!empty($codigo_postal)) {
            $cpProcesado = "C.P. " . $codigo_postal;
        }


        $ref_ubi = $constancia->propiedad?->referencias_ubicacion;

        $referencias_ubicacion = null;
        if (!empty($ref_ubi)) {
            $referencias_ubicacion = $ref_ubi;
        }

        // 2. Filtrar y unir (Calle + Colonia + CP)
        // array_filter eliminará automáticamente cualquier valor null o vacío
        $domicilioArr = array_filter([$calle, $coloniaProcesada, $cpProcesado]);

        if (empty($domicilioArr)) {
            $domicilio_propiedad = 'DOMICILIO CONOCIDO';
        } else {
            $domicilio_propiedad = implode(', ', $domicilioArr);
        }

        $domicilio_propiedad = $domicilio_propiedad;

        $localidad_propiedad = $constancia->propiedad?->localidad?->nombre;

        // 2. Buscamos el periodo que "contenga" esa fecha
        $periodo = Periodo::where('inicio', '<=', $constancia->fecha_emision)
            ->where('fin', '>=', $constancia->fecha_emision)
            ->first();


        // 2. Preparar el array de datos
        $data = [
            'prefijo_oficio' => $constancia->prefijo_oficio,
            'consecutivo_oficio' => str_pad($constancia->consecutivo_oficio, 4, '0', STR_PAD_LEFT),
            'nombre_propietario' => trim($constancia->propiedad->contacto->persona->nombre . ' ' . $constancia->propiedad->contacto->persona->apellidos),
            'texto_propietario' => $texto_propietario,
            'clave_catastral' => $claveFormateada ?? '000-000-000-000-000-000',
            'domicilio_propiedad' => $domicilio_propiedad,
            'localidad_propiedad' => $localidad_propiedad,
            'numero_asignado' => $constancia->numero_asignado,
            'numero_asignado_letra' => $constancia->numero_asignado_letra,
            'texto_interesado' => $texto_interesado,
            'fecha_emision' => $fecha_emision,
            'slogan' => $periodo?->slogan ?? '',
            'autoridad_firmante' => $constancia->autoridadFirmante->titulo . ' ' . $constancia->autoridadFirmante->nombre,
            'cargo' => $constancia->autoridadFirmante->cargo,
            'referencias_ubicacion' => $referencias_ubicacion,
            'coordenada_utm_x' => number_format($constancia->propiedad->coordenada_utm_x ?? 0, 2),
            'coordenada_utm_y' => number_format($constancia->propiedad->coordenada_utm_y ?? 0, 2),
        ];

        // 3. Lógica de diseño dinámico
        $textoCritico = $domicilio_propiedad . $localidad_propiedad . $referencias_ubicacion;
        $longitud = mb_strlen($textoCritico);
        $esLargo = $longitud > 162;
        $marginParrafos = $esLargo ? '6px' : '10px';
        $esMasLargo = $longitud > 200;
        $fontSize1 = $esMasLargo ? '7pt' : '8pt';  //CAMPO Referencias Ubicación
        $fontSize2 = $esMasLargo ? '8.5pt' : '10pt';  //Texto DOMICILIO
        $fontSize3 = $esMasLargo ? '8pt' : '9.5pt';  //Campo DOMICILIO

        $cuerpoProcesado = view($nombreVista, [
            'data' => $data,
            'marginParrafos' => $marginParrafos,
            'fontSize1' => $fontSize1,
            'fontSize2' => $fontSize2,
            'fontSize3' => $fontSize3
        ])->render();

        // 2. Renderizamos el CSS
        $css = view('pdf.css-1')->render();

        // 3. Armamos el HTML Final (Tu estructura deseada)
        $htmlFinal = "
        <html>
            <head>
                <title>Constancia de Número Oficial</title>
                <style> 
                    $css 
                    .parrafo-justificado { margin-bottom: $marginParrafos !important; }
                    .bloque-domicilio span { font-size: $fontSize1 !important; }
                    .bloque-domicilio span:first-child { font-size: $fontSize2 !important; }
                    .bloque-domicilio .texto-principal { font-size: $fontSize3 !important; line-height: 0.9 !important; }
                </style>
            </head>
            <body>
                $cuerpoProcesado
            </body>
        </html>
    ";

        // 4. Cargamos el PDF
        $pdf = Pdf::loadHTML($htmlFinal);
        $pdf->setOption([
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'chroot' => storage_path('fonts'),
        ]);

        Log::info('5. PDF generado');

        // return $pdf->setPaper('letter', 'portrait')->stream('constancia_num.pdf');
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="constancia_num.pdf"');
    }

    public function entregar(Request $request)
    {
        $idTramite = $request->id_tramite;

        if (!$idTramite) {
            return back()->with('error', 'No se proporcionó un ID válido para entregar.');
        }

        $tramite = Tramite::findOrFail($idTramite);

        // Ejecutamos la validación (asegúrate de que validaTramite use este ID para el ignore)
        $validator = $this->validaTramite($request);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $tramite->fecha_fin = now();
            $tramite->id_estatus = 99;
            $tramite->save();

            return redirect()->route('tramites')
                ->with('success', 'El trámite N° ' . str_pad($tramite->id, 4, '0', STR_PAD_LEFT) . ' se ha marcado como entregado.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function update_propiedad_geo(Request $request)
    {
        DB::beginTransaction();

        try {
            $propiedadOriginal = Propiedad::findOrFail($request->id_propiedad);

            $nuevaPropiedad = $propiedadOriginal->replicate();

            if ($request->codigo_postal) {
                $nuevaPropiedad->codigo_postal = $request->codigo_postal;
            }

            $nuevaPropiedad->coordenada_utm_x = $request->coordenada_utm_x;
            $nuevaPropiedad->coordenada_utm_y = $request->coordenada_utm_y;
            $nuevaPropiedad->referencias_ubicacion = $request->referencias_ubicacion;
            $nuevaPropiedad->save();

            $constancia = ConstanciaNumeroOficial::where('id_tramite', $request->id_tramite)->first();
            if ($constancia) {
                $constancia->id_propiedad = $nuevaPropiedad->id;
                $constancia->save();

                $this->registrarCambio($constancia, $request->id_tramite, 'ACTUALIZAR_PROPIEDAD_GEO');
            }

            DB::commit();

            return redirect()->route('tramites')->with('success', 'Propiedad actualizada exitosamente.');
        } catch (\Exception $e) {
            // Si hay CUALQUIER error, deshacemos todo lo que se haya hecho
            DB::rollBack();

            // Opcional: Loguear el error para depuración
            Log::error("Error al actualizar Geo en trámite {$request->id_tramite}: " . $e->getMessage());

            return back()->with('error', 'Error al actualizar Geo en trámite.');
        }
    }


    // Eliminamos el $id de los paréntesis porque la ruta no lo envía
    public function update(Request $request)
    {
        $idConstancia = $request->id_constancia;

        if (!$idConstancia) {
            return back()->with('error', 'No se proporcionó un ID válido para actualizar.');
        }

        $constancia = ConstanciaNumeroOficial::findOrFail($idConstancia);

        // Ejecutamos la validación (asegúrate de que validaTramite use este ID para el ignore)
        $validator = $this->validaTramite($request);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Usamos el método privado reutilizable que creamos antes
            $this->procesarConstancia($request, $constancia);

            return redirect()->route('tramites')
                ->with('success', 'Constancia actualizada exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    private function procesarDestinatarios($tram)
    {
        // Función auxiliar para género
        $obtenerGenero = function ($curp, $tipoBase) {
            if (!$curp || strlen($curp) < 11) return $tipoBase;
            $letraGenero = strtoupper($curp[10]);
            return ($tipoBase === 'PROPIETARIO' && $letraGenero === 'M') ? 'PROPIETARIA' : $tipoBase;
        };

        // 1. Construcción de lista
        $lista = [
            [
                'tipo' => $obtenerGenero($tram->solicitud->contacto->persona->curp ?? null, 'SOLICITANTE'),
                'nombre' => trim(($tram->solicitud->contacto->persona->nombre ?? '') . ' ' . ($tram->solicitud->contacto->persona->apellidos ?? ''))
            ],
            [
                'tipo' => $obtenerGenero($tram->propiedad->contacto->persona->curp ?? null, 'PROPIETARIO'),
                'nombre' => trim(($tram->propiedad->contacto->persona->nombre ?? '') . ' ' . ($tram->propiedad->contacto->persona->apellidos ?? ''))
            ],
            [
                'tipo' => 'RAZÓN SOCIAL',
                'nombre' => $tram->solicitud->razon_social->nombre ?? ''
            ]
        ];

        // 2. Filtro de elementos vacíos o inválidos
        $lista = array_filter($lista, function ($item) {
            $nombre = $item['nombre'];
            return !empty($nombre) && $nombre !== 'undefined undefined';
        });

        // 3. Eliminación de duplicados priorizando propietarios
        $unicos = [];
        foreach ($lista as $item) {
            $nombre = $item['nombre'];
            $esPropietario = in_array($item['tipo'], ['PROPIETARIO', 'PROPIETARIA']);

            if (!isset($unicos[$nombre]) || $esPropietario) {
                $unicos[$nombre] = $item;
            }
        }

        return array_values($unicos);
    }

    public function getTramite(Request $request)
    {
        if ($request->isMethod('get')) {

            return redirect()->back()->with('error', 'Sesión de trámite no válida.');
        }

        $idSolicitud = $request->input('id_solicitud');
        $tramite = Tramite::with([
            'solicitud',
            'tipoTramite',
            'tipoTramite.tipoTramite',
            'contacto',
            'contacto.persona',
            'propiedad',
            'documentos',
            'estatus',
            'propiedad.colonia',
            'propiedad.localidad',
            'propiedad.contacto',
            'propiedad.contacto.persona',
            'propiedad.tipo',
            'solicitud.razon_social',
            'solicitud.contacto',
            'solicitud.contacto.persona',
            'solicitud.documentos',
            'solicitud.documentos.requisitoDocumentacion',
            'estatus',
            'constanciaNumeroOficial',
            'constanciaNumeroOficial.propiedad',
            'constanciaNumeroOficial.propiedad.contacto',
            'constanciaNumeroOficial.propiedad.contacto.persona',
            'constanciaNumeroOficial.propiedad.colonia',
            'constanciaNumeroOficial.propiedad.localidad',
            'constanciaNumeroOficial.propiedad.tipo',
            'constanciaNumeroOficial.tramite',
            'constanciaNumeroOficial.tramite.documentos',
            'constanciaNumeroOficial.tramite.estatus',
            'constanciaNumeroOficial.tramite.justificacion'
        ])->where('id_solicitud', $idSolicitud)->first();

        DB::beginTransaction();

        try {
            if (!$tramite) {
                $solicitud = Solicitud::with([
                    'contacto',
                    'contacto.persona',
                    'propiedad',
                    'propiedad.contacto',
                    'propiedad.contacto.persona',
                    'tramites'
                ])->find($idSolicitud);


                //Agrego los trámites que tiene la solicitud
                foreach ($solicitud->tramites as $item) {
                    $idTramite = $item->id_tramite;

                    $nuevoTramite = Tramite::create([
                        'id_solicitud' => $idSolicitud,
                        'id_tramite'   => $idTramite, // Aquí cambia el ID en cada iteración
                        'fecha_inicio' => Carbon::today()->format('Y-m-d'),
                        'fecha_fin'    => null,
                        'id_contacto'  => $solicitud->contacto->id,
                        'id_propiedad' => $solicitud->propiedad->id,
                        'id_estatus'   => 1
                    ]);


                    if ($idTramite == 4) {
                        $tramite = $nuevoTramite;
                        $tramite->load(['propiedad', 'propiedad.contacto', 'contacto']);
                    }
                }
            }

            $destinatarios = $this->procesarDestinatarios($tramite);
            $todosEstatus = EstatusTramite::all();
            $solicitud = null;

            if ($tramite) {
                $initialData = $tramite;
            } else {
                $solicitud = Solicitud::with([
                    'contacto',
                    'contacto.persona',
                    'propiedad',
                    'propiedad.contacto',
                    'propiedad.contacto.persona'
                ])->find($idSolicitud);

                $initialData = [
                    'id' => null,
                    'solicitud' => $solicitud, // Tu objeto de solicitud
                    'propiedad' => $solicitud->propiedad ?? null, // Acceso seguro
                    'documento_generado' => $solicitud->documento_generado ?? null,
                    'created_at' => null
                ];
            }

            DB::commit();

            return Inertia::render('Tramites/Edit/ConstanciaDeNumeroOficial', [
                'initialData' => $initialData, // O la data que necesites
                'data' => $tramite ? $tramite : $solicitud,
                'documentoData' => $initialData['documento_generado'] ?? null,
                'destinatarios' => $destinatarios,
                'todosEstatus' => $todosEstatus,
                'userAuth' => Auth::user(),
                'isPage' => true
            ]);
        } catch (\Exception $e) {
            // Si hay CUALQUIER error, deshacemos todo lo que se haya hecho
            DB::rollBack();

            // Opcional: Loguear el error para depuración
            Log::error("Error al abrir el trámite " . $e->getMessage());

            return back()->with('error', 'Error al abrir el trámite.');
        }
    }

    public function getConstancia($idConstancia)
    {
        $constancia = ConstanciaNumeroOficial::with([
            'propiedad',
            'propiedad.contacto',
            'propiedad.contacto.persona',
            'propiedad.colonia',
            'propiedad.localidad',
            'propiedad.tipo',
            'tramite',
            'tramite.documentos',
            'tramite.estatus',
            'tramite.justificacion'
        ])->find($idConstancia);

        return response()->json([
            'status' => 'success',
            'constancia' => $constancia
        ]);
    }
}
