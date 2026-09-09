<?php

namespace App\Http\Controllers;

use App\Models\CatalogoTramite;
use Illuminate\Http\Request;
use App\Models\Solicitud;
use App\Models\EstatusSolicitud;
use App\Models\Persona;
use App\Models\Colonia;
use App\Models\Localidad;
use App\Models\TipoPropiedad;
use App\Models\DestinoObra;
use App\Models\SectorTramite;
use App\Models\TipoTramite;
use App\Models\Contacto;
use App\Models\Propiedad;
use App\Models\SolicitudTramite;
use App\Models\SolicitudReferencia;
use App\Models\SolicitudRazonSocial;
use App\Models\SolicitudAceptada;
use App\Models\Periodo;
use App\Models\CroquisAux;
use App\Models\ConfiguracionUsuario;
use App\Models\RequisitoDocumentacion;
use App\Models\Tramite;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Mail\SolicitudMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Helpers\SettingsHelper;
use App\Models\DomicilioNotificacion;
use Illuminate\Database\QueryException;
use Throwable;

class SolicitudController extends Controller
{
    public $activeTab;
    public $ID_CONSTANCIA_UBICACION = 18;

    private $obfuscationDecodeMap = [
        'b' => 'a',
        'c' => 'b',
        'd' => 'c',
        'e' => 'd',
        'f' => 'e',
        'g' => 'f',
        'h' => 'g',
        'i' => 'h',
        'j' => 'i',
        'k' => 'j',
        'l' => 'k',
        'm' => 'l',
        'n' => 'm',
        'o' => 'n',
        'p' => 'o',
        'q' => 'p',
        'r' => 'q',
        's' => 'r',
        't' => 's',
        'u' => 't',
        'v' => 'u',
        'w' => 'v',
        'x' => 'w',
        'y' => 'x',
        'z' => 'y',
        'a' => 'z', // La 'a' ofuscada vuelve a ser 'z'

        'B' => 'A',
        'C' => 'B',
        'D' => 'C',
        'E' => 'D',
        'F' => 'E',
        'G' => 'F',
        'H' => 'G',
        'I' => 'H',
        'J' => 'I',
        'K' => 'J',
        'L' => 'K',
        'M' => 'L',
        'N' => 'M',
        'O' => 'N',
        'P' => 'O',
        'Q' => 'P',
        'R' => 'Q',
        'S' => 'R',
        'T' => 'S',
        'U' => 'T',
        'V' => 'U',
        'W' => 'V',
        'X' => 'W',
        'Y' => 'X',
        'Z' => 'Y',
        'A' => 'Z', // La 'A' ofuscada vuelve a ser 'Z'

        '1' => '0',
        '2' => '1',
        '3' => '2',
        '4' => '3',
        '5' => '4',
        '6' => '5',
        '7' => '6',
        '8' => '7',
        '9' => '8',
        '0' => '9', // El '0' ofuscado vuelve a ser '9'
    ];

    public function getRequisitos(Request $request)
    {
        // Obtener y validar el parámetro 'tramites'
        $tramitesString = $request->query('tramites');

        if (empty($tramitesString)) {
            return response()->json(['error' => 'No se han proporcionado IDs de trámites.'], 400);
        }

        // Conversión: CADENA '1,2,3' a ARRAY DE ENTEROS [1, 2, 3]
        $tramitesIds = array_map('intval', explode(',', $tramitesString));

        try {
            // ----------------------------------------------------------------------------------
            // 1. FILTRO Y CARGA DE RELACIÓN: Obtener requisitos que pertenecen a los trámites
            // ----------------------------------------------------------------------------------
            $requisitos = RequisitoDocumentacion::whereHas('tramites', function ($query) use ($tramitesIds) {
                // 1. Filtra por los IDs de trámites
                $query->whereIn('catalogo_tramites.id', $tramitesIds);

                // 2. APLICA EL FILTRO 'activo' DE LA TABLA PIVOTE AQUÍ (INNER JOIN)
                $query->where('catalogo_tramites_requisitos.activo', true); // <-- ¡CORRECCIÓN CLAVE!
            })
                ->where('id', '>', 1)
                // Cargar la relación 'tramites' para acceder a la tabla pivote, y filtrarla
                ->with(['tramites' => function ($query) use ($tramitesIds) {
                    $query->whereIn('catalogo_tramites.id', $tramitesIds)
                        ->where('catalogo_tramites_requisitos.activo', true); // Mantener este para filtrar la relación cargada
                }])
                ->orderBy('nombre', 'asc')
                ->get();

            // Log::info('REQUISITOS', $requisitos->toArray());

            // ----------------------------------------------------------------------------------

            if ($requisitos->isEmpty()) {
                return response()->json(['requisitos' => [], 'mensaje' => 'No se encontraron requisitos para los trámites seleccionados.'], 200);
            }


            // ----------------------------------------------------------------------------------
            // 2. ADJUNTAR LA PROPIEDAD 'obligatorio'
            // ----------------------------------------------------------------------------------
            $requisitosConObligatoriedad = $requisitos->map(function ($requisito) {

                // Un requisito puede estar asociado a varios trámites de la lista $tramitesIds.
                // Para determinar si es obligatorio en el contexto de la solicitud, 
                // basta con verificar el campo 'obligatorio' en la tabla pivote de CUALQUIERA 
                // de los trámites cargados. 
                // NOTA: Si la obligatoriedad puede variar entre trámites seleccionados, 
                // se debe decidir la lógica (ej: ¿es obligatorio si lo es para AL MENOS uno?).
                // Aquí usamos la lógica de verificar el primer trámite cargado.

                $primerTramite = $requisito->tramites->first();

                if ($primerTramite) {
                    // Adjuntamos la propiedad 'obligatorio' al objeto requisito.
                    // Aseguramos que sea un booleano (bool) ya que Vue lo espera así.
                    // Asumimos que la columna en la tabla pivote se llama 'obligatorio'.
                    $requisito->obligatorio = (bool) $primerTramite->pivot->obligatorio;

                    // Limpiamos: Eliminar la relación 'tramites' para una respuesta JSON más limpia
                    // y ligera, ya que Vue solo necesita la propiedad 'obligatorio'.
                    unset($requisito->tramites);
                } else {
                    // Si por alguna razón la relación eager loaded está vacía, se asume NO obligatorio.
                    $requisito->obligatorio = false;
                }

                return $requisito;
            });

            // ----------------------------------------------------------------------------------
            // 3. RETORNO DE RESPUESTA
            // ----------------------------------------------------------------------------------
            return response()->json([
                // Devolvemos la colección mapeada
                'requisitos' => $requisitosConObligatoriedad
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error("Error de consulta en getRequisitos: " . $e->getMessage());
            return response()->json(['error' => 'Error al consultar la base de datos de requisitos.'], 500);
        } catch (\Exception $e) {
            Log::error("Excepción inesperada en getRequisitos: " . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error inesperado en el servidor.'], 500);
        }
    }

    public function getPersona(Request $request, $curp)
    {
        if ($request->query('tipo') === 'solicitante') {
            $persona = Persona::with('solicitante', 'solicitante.persona', 'solicitante.domicilio_notificacion')
                ->where('curp', $curp)
                ->where('activa', 1)->first();
        } elseif ($request->query('tipo') === 'propietario') {
            $persona = Persona::with('propietario', 'propietario.persona', 'propietario.domicilio_notificacion')
                ->where('curp', $curp)
                ->where('activa', 1)->first();
        } else {
            $persona = Persona::with('solicitante', 'solicitante.colonia', 'solicitante.localidad')
                ->where('curp', $curp)
                ->where('activa', 1)->first();
        }


        return response()->json([
            'persona' => $persona
        ]);
    }

    public function getSolicitud($id)
    {
        $solicitud = Solicitud::with([
            'contacto',
            'propiedad',
            'estatus',
            'destino_obra',
            'tramites.tramite.tipoTramite',
            'contacto.persona',
            'propiedad.tipo',
            'propiedad.contacto',
            'propiedad.contacto.persona',
            'propiedad.colonia',
            'propiedad.localidad',
            'sectorTramite',
            'referencia',
            'referencia.tipo_propiedad',
            'referencia.localidad',
            'croquis_aux',
            'razon_social',
            'propiedad.contacto.domicilio_notificacion',
            'contacto.domicilio_notificacion',
            'requisitos_docs' => function ($query) {
                $query->select('requisitos_documentacion.id');
            }
        ])->findOrFail($id);

        $requisitos = collect();

        $i = 0;

        foreach ($solicitud->tramites as $tram) {
            $tramitesIds[$i] = $tram->tramite->id;
            $i++;
        }

        $requisitos = RequisitoDocumentacion::where('id', '>', 1)->whereHas('tramites', function ($query) use ($tramitesIds) {
            // La doble cláusula whereIn es redundante pero funcional.
            $query->whereIn('catalogo_tramites.id', $tramitesIds)
                ->where('catalogo_tramites_requisitos.activo', true);
            // $query->whereIn('id', $tramitesIds); // Se puede omitir si la anterior es suficiente
        })
            // Cargar la relación 'tramites', pero solo para los IDs relevantes.
            ->with(['tramites' => function ($query) use ($tramitesIds) {
                $query->whereIn('catalogo_tramites.id', $tramitesIds);
            }])
            ->orderBy('nombre', 'asc')
            ->get();

        // 💡 Mapear la colección para adjuntar el campo 'obligatorio' al nivel raíz.
        $requisitos = $requisitos->map(function ($requisito) {

            // El requisito puede estar relacionado a múltiples trámites.
            // Tomamos la información de la tabla pivote del primer trámite encontrado.
            $primerTramite = $requisito->tramites->first();

            if ($primerTramite) {
                // Asignamos el valor booleano 'obligatorio' al modelo requisito.
                // Asegúrate de que sea un booleano, ya que tu frontend lo espera así.
                $requisito->obligatorio = (bool) $primerTramite->pivot->obligatorio;

                // Opcional: Eliminar la relación 'tramites' de la respuesta JSON
                // para que tu frontend solo tenga la lista limpia que espera.
                unset($requisito->tramites);
            } else {
                // En caso de error lógico o data faltante, define un default seguro.
                $requisito->obligatorio = false;
            }

            return $requisito;
        });

        return response()->json([
            'solicitud' => $solicitud,
            'requisitos' => $requisitos
        ]);
    }

    public function getPropiedad($claveCatastral)
    {
        $propiedad = Propiedad::with('colonia', 'localidad', 'tipo', 'solicitudes', 'contacto', 'contacto.persona')
            ->where('clave_catastral', $claveCatastral)
            ->where('activa', 1)->first();

        return response()->json([
            'propiedad' => $propiedad,
        ]);
    }

    public function getPropiedadSolicitud($idSolicitud)
    {
        $solicitud = Solicitud::with('propiedad')->findOrFail($idSolicitud);

        $propiedad = $solicitud->propiedad;
        if ($propiedad) {
            $propiedad->load('colonia', 'localidad', 'tipo', 'solicitudes', 'contacto', 'contacto.persona');
        }

        return response()->json([
            'propiedad' => $propiedad,
        ]);
    }

    public function getColonias(Request $request)
    {
        $nombre = $request->query('nombre');
        $colonias = null;

        if (strlen($nombre) > 0) {
            $colonias = Colonia::where('nombre', 'like', '%' . $nombre . '%')
                ->limit(6)
                ->get();
        }

        return response()->json([
            'colonias' => $colonias,
        ]);
    }

    public function getLocalidades(Request $request)
    {
        $nombre = $request->query('nombre');
        $localidades = null;

        if (strlen($nombre) > 0) {
            $localidades = Localidad::where('nombre', 'like', '%' . $nombre . '%')
                ->limit(6)
                ->get();
        }

        return response()->json([
            'localidades' => $localidades,
        ]);
    }

    public function getPersonas(Request $request)
    {
        $nombre = $request->query('nombre');
        $personas = null;

        if (strlen($nombre) > 0) {
            $personas = Persona::with(['solicitante', 'propietario'])
                ->where('nombre', 'like', '%' . $nombre . '%')
                ->orWhere('apellidos', 'like', '%' . $nombre . '%')
                ->limit(6)
                ->get();
        }

        return response()->json([
            'personas' => $personas,
        ]);
    }

    // Prepara impresión (POST)
    public function printPDFPrepare()
    {
        // return redirect()->back();
        // return true;
    }

    public function printPreviewPDFPrepare()
    {
        // return redirect()->back();
        // return true;
    }

    // Muestra PDF (GET)
    public function printPDF($id)
    {
        $solicitud = Solicitud::with([
            'contacto',
            'propiedad',
            'estatus',
            'destino_obra',

            // 1. Relación para obtener los Trámites de la Solicitud.
            'tramites' => function ($query) {
                // Dentro de 'tramites', cargamos:
                $query->with([
                    'tramite.tipoTramite',

                    'tramite.requisitos' => function ($queryRequisitos) {
                        $queryRequisitos->where('id', '>', 1);
                    }
                ]);
            },

            'requisitos_docs',
            'propiedad.contacto',
            'propiedad.contacto.domicilio_notificacion'
        ])->findOrFail($id);

        $tramitesCatalogo = $solicitud->tramites
            ->pluck('tramite') // Extrae el modelo CatalogoTramite de la relación 'tramites'
            ->filter();         // Elimina posibles nulos

        // 2. Usar flatMap para extraer la colección de 'requisitos' de cada trámite 
        //    y unirlos en una sola colección.
        $requisitosRequeridosUnicos = $tramitesCatalogo
            ->flatMap(fn($tramite) => $tramite->requisitos)
            ->unique('id')
            ->values();

        $idsEntregados = $solicitud->requisitos_docs
            ->pluck('id')
            ->filter()
            ->toArray();

        // 3. Mapear e Inyectar la Propiedad 'entregado'
        $requisitosConEstado = $requisitosRequeridosUnicos->map(function ($requisito) use ($idsEntregados) {
            $requisito->entregado = in_array($requisito->id, $idsEntregados);
            return $requisito;
        });
        $fecha = $solicitud->fecha_ingreso;

        $periodo = Periodo::whereDate('inicio', '<=', $fecha)
            ->whereDate('fin', '>=', $fecha)
            ->first();

        // Accede a los trámites ya agrupados y ordenados a través del accesor del modelo.
        // La variable $tramitesAgrupados ya contiene la estructura que necesitas para el PDF.
        $tramitesAgrupados = $solicitud->grouped_tramites;

        // Si aún necesitas la cantidad de tipos de trámite, la puedes obtener de $tramitesAgrupados
        $cantidadTiposTramite = $tramitesAgrupados->count();

        $css = view('solicitudes.pdfCSS')->render();

        if ($solicitud->propiedad) {
            $imgCroquis = $solicitud->propiedad->img_croquis; // El nombre de tu archivo de imagen
        } else {
            $imgCroquis = $solicitud->croquis_aux->img; // El nombre de tu archivo de imagen
        }

        $rutaRelativa = 'croquis/' . $imgCroquis;
        $pathCompleto = storage_path('app/public/' . $rutaRelativa);

        if (file_exists($pathCompleto)) {
            list($width, $height) = getimagesize($pathCompleto);
            $imagenAlta = ($height > $width);
        } else {
            $imagenAlta = false; // Valor por defecto si no encuentra la imagen
        }


        // Pasa TODAS las variables necesarias a tu vista.
        // Incluimos $tramitesAgrupados para que puedas iterar sobre ella en la vista.
        $html = view('solicitudes.pdfSolicitud', compact('solicitud', 'periodo', 'css', 'cantidadTiposTramite', 'tramitesAgrupados', 'requisitosConEstado', 'imagenAlta'))->render();

        return Pdf::loadHTML($html)
            ->setPaper('letter', 'portrait')
            ->stream('solicitud.pdf');
    }

    public function printPreviewPDF($id)
    {
        $solicitud = Solicitud::with([
            'contacto',
            'propiedad',
            'estatus',
            'destino_obra',

            // 1. Relación para obtener los Trámites de la Solicitud.
            'tramites' => function ($query) {
                // Dentro de 'tramites', cargamos:
                $query->with([
                    'tramite.tipoTramite',

                    'tramite.requisitos' => function ($queryRequisitos) {
                        $queryRequisitos->where('id', '>', 1);
                    }
                ]);
            },

            'requisitos_docs',
            'propiedad.contacto',
            'propiedad.contacto.domicilio_notificacion'
        ])->findOrFail($id);

        // $tramitesCatalogo = $solicitud->tramites
        // ->pluck('tramite') // Extrae el modelo CatalogoTramite de la relación 'tramites'
        // ->filter();         // Elimina posibles nulos

        $tramitesCatalogo = $solicitud->tramites
            ->pluck('tramite') // Obtenemos los modelos CatalogoTramite
            ->filter()         // Limpiamos nulos
            ->each(function ($tramite) {
                // Entramos a los requisitos de cada trámite
                $requisitosFiltrados = $tramite->requisitos->filter(function ($requisito) {
                    // Accedemos al atributo 'activo' dentro del objeto pivot
                    // Importante: usamos == 1 por si MySQL lo devuelve como string o int
                    return $requisito->pivot && $requisito->pivot->activo == 1;
                });

                // Reasignamos la relación ya filtrada al objeto original
                $tramite->setRelation('requisitos', $requisitosFiltrados);
            });

        // dd($tramitesCatalogo);

        // 2. Usar flatMap para extraer la colección de 'requisitos' de cada trámite 
        //    y unirlos en una sola colección.
        $requisitosRequeridosUnicos = $tramitesCatalogo
            ->flatMap(fn($tramite) => $tramite->requisitos)
            ->unique('id')
            ->values();

        $idsEntregados = $solicitud->requisitos_docs
            ->pluck('id')
            ->filter()
            ->toArray();

        // 3. Mapear e Inyectar la Propiedad 'entregado'
        $requisitosConEstado = $requisitosRequeridosUnicos->map(function ($requisito) use ($idsEntregados) {
            $requisito->entregado = in_array($requisito->id, $idsEntregados);
            return $requisito;
        });

        // dd($requisitosConEstado);

        $fecha = $solicitud->fecha_ingreso;

        $periodo = Periodo::whereDate('inicio', '<=', $fecha)
            ->whereDate('fin', '>=', $fecha)
            ->first();

        // Accede a los trámites ya agrupados y ordenados a través del accesor del modelo.
        // La variable $tramitesAgrupados ya contiene la estructura que necesitas para el PDF.
        $tramitesAgrupados = $solicitud->grouped_tramites;

        // Si aún necesitas la cantidad de tipos de trámite, la puedes obtener de $tramitesAgrupados
        $cantidadTiposTramite = $tramitesAgrupados->count();

        $css = view('solicitudes.pdfCSS')->render();

        // Pasa TODAS las variables necesarias a tu vista.
        // Incluimos $tramitesAgrupados para que puedas iterar sobre ella en la vista.
        $html = view('solicitudes.pdfSolicitudPreview', compact('solicitud', 'periodo', 'css', 'cantidadTiposTramite', 'tramitesAgrupados', 'requisitosConEstado'))->render();

        return Pdf::loadHTML($html)->stream('pre-solicitud.pdf');
    }

    public function regresaRangoFecha($idRangoFecha)
    {
        // --- Definir los rangos de fechas predefinidos ---
        // Estas funciones anónimas encapsulan la lógica de cálculo para cada rango.
        // Los IDs y su lógica DEBEN coincidir con tus shortcuts del frontend y los datos del seeder.
        $predefinedRanges = [
            1 => function () { // Hoy
                return [Carbon::today(), Carbon::today()];
            },
            2 => function () { // Semana Actual (Lunes a Hoy)
                $today = Carbon::today();
                $start = $today->copy()->startOfWeek(Carbon::MONDAY);
                // Ajuste para el caso en que Carbon's startOfWeek pueda dar una fecha futura
                if ($start->isFuture() && !$start->isSameDay($today)) {
                    $start->subWeek();
                }
                return [$start, $today];
            },
            3 => function () { // Mes Actual (Primer día del mes a Hoy)
                return [Carbon::today()->startOfMonth(), Carbon::today()];
            },
            4 => function () { // Año Actual (Primer día del año a Hoy)
                return [Carbon::today()->startOfYear(), Carbon::today()];
            },
            5 => function () { // Semana Pasada (Lunes a Domingo de la semana anterior)
                $lastWeekEnd = Carbon::today()->startOfWeek(Carbon::MONDAY)->subDay(); // Domingo de la semana pasada
                $lastWeekStart = $lastWeekEnd->copy()->subDays(6); // Lunes de la semana pasada
                return [$lastWeekStart, $lastWeekEnd];
            },
            6 => function () { // Mes Pasado (Todo el mes anterior)
                return [Carbon::today()->subMonth()->startOfMonth(), Carbon::today()->subMonth()->endOfMonth()];
            },
            7 => function () { // Año Pasado (Todo el año anterior)
                return [Carbon::today()->subYear()->startOfYear(), Carbon::today()->subYear()->endOfYear()];
            },
            8 => function () { // Últ. Semana (Últimos 7 días incluyendo hoy)
                $end = Carbon::today();
                $start = $end->copy()->subDays(6); // 6 días atrás para incluir hoy (total de 7 días)
                return [$start, $end];
            },
            9 => function () { // Últ. Mes (Últimos 30 días incluyendo hoy)
                $end = Carbon::today();
                $start = $end->copy()->subMonthsNoOverflow(1); // 1 mes atrás, manteniendo el día si es posible
                return [$start, $end];
            },
            10 => function () { // Últ. Año (Últimos 365 días incluyendo hoy)
                $end = Carbon::today();
                $start = $end->copy()->subYearsNoOverflow(1);
                return [$start, $end];
            },
            // El rango 'Custom' (ID 99) no tiene una lógica de cálculo fija aquí,
            // ya que sus fechas se definirían explícitamente por el usuario.
            // Esta función solo se ocupa de los rangos predefinidos.
            // Para el ID 99, podrías retornar un rango por defecto o indicar que no es un rango fijo.
            99 => function () { // Custom
                // Si 'Custom' se selecciona, podrías necesitar otras entradas del usuario
                // o un rango muy amplio por defecto si no hay fechas específicas.
                // Aquí, devolveremos el "Mes Actual" como un fallback lógico para "Custom" si se busca así
                // y no hay fechas específicas en la petición.
                return [Carbon::today()->startOfMonth(), Carbon::today()];
            },
        ];

        // Asegúrate de que el ID exista en tus rangos predefinidos, si no, usa el ID por defecto.
        if (!isset($predefinedRanges[$idRangoFecha])) {
            $idRangoFecha = 99; // Fallback al ID por defecto (Mes Actual)
        }

        // Ejecuta la función asociada al ID para obtener los objetos Carbon
        list($fechaInicioCarbon, $fechaFinCarbon) = $predefinedRanges[$idRangoFecha]();

        // Regresa las fechas formateadas como strings 'YYYY-MM-DD'
        return [$fechaInicioCarbon->toDateString(), $fechaFinCarbon->toDateString()];
    }

    public function obtenerSolicitudes(
        $fechaIngresoInicioQuery,
        $fechaIngresoFinQuery,
        $fechaAceptacionInicioQuery,
        $fechaAceptacionFinQuery,
        $numQuery,
        $folioQuery,
        $nombreQuery,
        $claveCatastralQuery,
        $tiposTramitesQuery,
        $tramitesQuery,
        $localidadesQueryFiltradas,
        $estatusQuery,
        $sortColumn,
        $sortDirection
    ) {
        $solicitudesQuery = Solicitud::join('estatus_solicitudes', 'solicitudes.id_estatus', '=', 'estatus_solicitudes.id')
            ->join('contactos as contactos_solicitantes', 'solicitudes.id_contacto', '=', 'contactos_solicitantes.id')
            ->join('personas as personas_solicitantes', 'contactos_solicitantes.id_persona', '=', 'personas_solicitantes.id')
            ->leftJoin('propiedades', 'solicitudes.id_propiedad', '=', 'propiedades.id')
            ->leftJoin('contactos as contactos_propietarios', 'propiedades.id_contacto', '=', 'contactos_propietarios.id')
            ->leftJoin('personas as personas_propietarios', 'contactos_propietarios.id_persona', '=', 'personas_propietarios.id')
            ->leftJoin('solicitudes_tramites', 'solicitudes.id', '=', 'solicitudes_tramites.id_solicitud')
            ->leftJoin('catalogo_tramites', 'solicitudes_tramites.id_tramite', '=', 'catalogo_tramites.id')
            ->leftJoin('solicitudes_razones_sociales', 'solicitudes.id', '=', 'solicitudes_razones_sociales.id_solicitud')
            ->leftJoin('solicitud_referencias', 'solicitudes.id', '=', 'solicitud_referencias.id_solicitud')

            ->leftJoin('localidades AS localidades_propiedades', 'propiedades.id_localidad', '=', 'localidades_propiedades.id')
            ->leftJoin('localidades AS localidades_referencias', 'solicitud_referencias.id_localidad', '=', 'localidades_referencias.id')

            ->with([
                'estatus',
                'contacto',
                'contacto.persona',
                'propiedad',
                'propiedad.contacto',
                'propiedad.contacto.persona',
                'propiedad.colonia',
                'propiedad.localidad',
                'propiedad.tipo',
                'destino_obra',
                'tramites',
                'tramites.tramite',
                'razon_social',
                'referencia',
                'referencia.localidad',
                'tramites.tramite.tipoTramite',
                'aceptada',
                'tramites.tramite.requisitos',
                'tramitesAsignados'
            ])
            ->selectRaw('solicitudes.id, solicitudes.fecha_ingreso, solicitudes.id_contacto,
                             solicitudes.folio_digital, solicitudes.id_propiedad,
                             solicitudes.id_destino_obra, solicitudes.id_estatus, solicitudes.folio,
                             solicitudes.fecha_aceptacion,
                             COALESCE(CONCAT(personas_propietarios.nombre, personas_propietarios.apellidos), (CONCAT(personas_solicitantes.nombre, personas_solicitantes.apellidos))) AS nombre_solicitante_propietario,
                             COALESCE(localidades_propiedades.nombre, localidades_referencias.nombre) AS nombre_localidad_prioritario,
                             GROUP_CONCAT(catalogo_tramites.nombre ORDER BY catalogo_tramites.nombre ASC) as tramites_nombres,
                             GROUP_CONCAT(catalogo_tramites.id ORDER BY catalogo_tramites.nombre ASC) as id_tramites')
            ->groupBy(
                'solicitudes.id',
                'solicitudes.fecha_ingreso',
                'solicitudes.id_contacto',
                'solicitudes.folio_digital',
                'solicitudes.id_propiedad',
                'solicitudes.id_destino_obra',
                'solicitudes.id_estatus',
                'solicitudes.folio',
                'solicitudes.fecha_aceptacion',
                'nombre_solicitante_propietario',
                'nombre_localidad_prioritario'
            );

        // 2. Lógica Condicional para el Filtro de Fechas
        // 💡 Aplicamos el filtro de fechas solo SI $numQuery está vacío (o no tiene valor significativo).
        if ((empty($numQuery) || !is_numeric($numQuery)) && (empty($folioQuery) || !is_numeric($folioQuery))) {
            if ((empty($fechaAceptacionInicioQuery) || is_null($fechaAceptacionInicioQuery)) && (empty($fechaAceptacionFinQuery) || is_null($fechaAceptacionFinQuery))) {
                $solicitudesQuery->whereBetween('solicitudes.fecha_ingreso', [$fechaIngresoInicioQuery, $fechaIngresoFinQuery]);
            } else {
                $solicitudesQuery->whereBetween('solicitudes.fecha_aceptacion', [$fechaAceptacionInicioQuery, $fechaAceptacionFinQuery]);
            }
        }

        if ($nombreQuery) {
            $solicitudesQuery->where(function ($query) use ($nombreQuery) {
                // Búsqueda por nombre de solicitante
                $query->where(function ($q1) use ($nombreQuery) {
                    $q1->where('personas_solicitantes.nombre', 'like', '%' . $nombreQuery . '%')
                        ->orWhere('personas_solicitantes.apellidos', 'like', '%' . $nombreQuery . '%');
                })
                    // Búsqueda por nombre de propietario
                    ->orWhere(function ($q2) use ($nombreQuery) {
                        $q2->where('personas_propietarios.nombre', 'like', '%' . $nombreQuery . '%')
                            ->orWhere('personas_propietarios.apellidos', 'like', '%' . $nombreQuery . '%');
                    })
                    // Nueva búsqueda por nombre de razón social
                    ->orWhere('solicitudes_razones_sociales.nombre', 'like', '%' . $nombreQuery . '%');
            });
        }

        if ($numQuery) {
            // 1. Limpiamos: '0010' -> '0010'
            $cleanedNum = preg_replace('/[^0-9]/', '', $numQuery);

            if (!empty($cleanedNum)) {

                // 2. Normalizamos: '0010' -> '10'
                // Utilizamos ltrim() para quitar los ceros a la izquierda.
                $normalizedNum = ltrim($cleanedNum, '0');

                // Si la cadena queda vacía (ej: si el input era '000'), usamos '0' para prevenir fallos.
                if ($normalizedNum === '') {
                    $normalizedNum = '0';
                }

                // 💡 Aplicar la lógica compleja SOLO si la entrada normalizada es corta (menos de 4 dígitos)
                if (strlen($normalizedNum) < 4) {

                    $solicitudesQuery->where(function ($query) use ($normalizedNum) {

                        // Opción A: Es el ID exacto (e.g., ID = 10)
                        // Buscamos la coincidencia exacta con el número normalizado '10'
                        $query->where('solicitudes.id', $normalizedNum)

                            // Opción B: Es un ID largo que termina en el número (e.g., 1010, 2010)
                            ->orWhere(function ($q) use ($normalizedNum) {
                                // El patrón es %10
                                $searchPattern = '%' . $normalizedNum;

                                // Restricción 1: Debe tener 4 o más dígitos
                                $q->whereRaw('LENGTH(solicitudes.id) >= 4')
                                    // Restricción 2: Debe terminar en el número normalizado ('%10')
                                    ->whereRaw('CAST(solicitudes.id AS CHAR) LIKE ?', [$searchPattern]);
                            });
                    });
                }
            }
        }

        if ($folioQuery) {
            // 1. Limpiamos: '0010' -> '0010'
            $cleanedFolio = preg_replace('/[^0-9Nn]/', '', $folioQuery);

            if (!empty($cleanedFolio)) {
                if ($cleanedFolio == 'N' || $cleanedFolio == 'n') {
                    $solicitudesQuery->whereNull('solicitudes.folio');
                } else {
                    // 2. Normalizamos: '0010' -> '10'
                    // Utilizamos ltrim() para quitar los ceros a la izquierda.
                    $normalizedFolio = ltrim($cleanedFolio, '0');

                    // Si la cadena queda vacía (ej: si el input era '000'), usamos '0' para prevenir fallos.
                    if ($normalizedFolio === '') {
                        $normalizedFolio = '0';
                    }

                    // 💡 Aplicar la lógica compleja SOLO si la entrada normalizada es corta (menos de 4 dígitos)
                    if (strlen($normalizedFolio) < 4) {

                        $solicitudesQuery->where(function ($query) use ($normalizedFolio) {

                            // Opción A: Es el ID exacto (e.g., ID = 10)
                            // Buscamos la coincidencia exacta con el número normalizado '10'
                            $query->where('solicitudes.folio', $normalizedFolio)

                                // Opción B: Es un ID largo que termina en el número (e.g., 1010, 2010)
                                ->orWhere(function ($q) use ($normalizedFolio) {
                                    // El patrón es %10
                                    $searchPattern = '%' . $normalizedFolio;

                                    // Restricción 1: Debe tener 4 o más dígitos
                                    $q->whereRaw('LENGTH(solicitudes.folio) >= 4')
                                        // Restricción 2: Debe terminar en el número normalizado ('%10')
                                        ->whereRaw('CAST(solicitudes.folio AS CHAR) LIKE ?', [$searchPattern]);
                                });
                        });
                    }
                }
            }
        }

        if ($claveCatastralQuery) {
            $solicitudesQuery->where('propiedades.clave_catastral', 'LIKE', $claveCatastralQuery . '%');
        }

        if (!is_array($tiposTramitesQuery)) {
            $tiposTramitesQuery = [$tiposTramitesQuery];
        }

        if (!empty($tiposTramitesQuery)) {
            $solicitudesQuery->whereIn('catalogo_tramites.id_tipo', $tiposTramitesQuery);
        }

        $solicitudesFiltradasIdsPrev = (clone $solicitudesQuery)->pluck('solicitudes.id');

        if (!is_array($tramitesQuery)) {
            $tramitesQuery = [$tramitesQuery];
        }

        if (!empty($tramitesQuery)) {
            $solicitudesQuery->where(function ($query) use ($tramitesQuery) {
                // Si seleccionaron trámites con ID (no null)
                $tramiteIds = array_filter($tramitesQuery, fn($id) => !is_null($id));

                if (!empty($tramiteIds)) {
                    $query->whereIn('solicitudes_tramites.id_tramite', $tramiteIds);
                }

                // Si seleccionaron la opción "SIN TRÁMITES" (id null)
                if (in_array(null, $tramitesQuery, true)) {
                    $query->orWhereNull('solicitudes_tramites.id_tramite');
                }
            });
        }

        // 1. Aseguramos que $localidadesQueryFiltradas sea un array.
        if (!is_array($localidadesQueryFiltradas)) {
            $localidadesQueryFiltradas = [$localidadesQueryFiltradas];
        }

        if (!empty($localidadesQueryFiltradas)) {
            // 1. Aseguramos que $localidadesQueryFiltradas sea un array.
            if (!is_array($localidadesQueryFiltradas)) {
                $localidadesQueryFiltradas = [$localidadesQueryFiltradas];
            }

            if (!empty($localidadesQueryFiltradas)) {
                // Normalización: 
                // Separamos los IDs reales (incluye ID 0) de la opción "N/A" (la cadena 'null').
                $localidadIds = [];
                $incluirNulos = false;

                foreach ($localidadesQueryFiltradas as $id) {
                    // La cadena 'null' se convierte en el indicador para el filtro IS NULL.
                    if ($id === '-99' || is_null($id)) {
                        $incluirNulos = true;
                    } elseif (is_numeric($id) || $id !== '') {
                        // Incluye IDs numéricos (cadenas o enteros), como '1' o 0.
                        $localidadIds[] = $id;
                    }
                }

                // Si no hay nada que filtrar, salimos.
                if (empty($localidadIds) && !$incluirNulos) {
                    return;
                }

                $solicitudesQuery->where(function ($query) use ($localidadIds, $incluirNulos) {

                    $hasExistingCondition = false;

                    // A) FILTRAR POR IDs REALES (Priorización: Propiedad > Referencia)
                    if (!empty($localidadIds)) {
                        $query->where(function ($q) use ($localidadIds) {
                            // 1. Coincidencia en la Propiedad (Prioridad)
                            $q->whereIn('propiedades.id_localidad', $localidadIds)
                                // O (OR)
                                // 2. Coincidencia en la Referencia, SÓLO si la Propiedad NO tiene ID de Localidad.
                                ->orWhere(function ($q_ref) use ($localidadIds) {
                                    $q_ref->whereNull('propiedades.id_localidad')
                                        ->whereIn('solicitud_referencias.id_localidad', $localidadIds);
                                });
                        });
                        $hasExistingCondition = true;
                    }

                    // B) FILTRAR POR NULL ("N/A")
                    if ($incluirNulos) {
                        // Si solo se seleccionó 'null', usamos 'where'. Si se seleccionó con IDs, usamos 'orWhere'.
                        $method = $hasExistingCondition ? 'orWhere' : 'where';

                        $query->$method(function ($q_null) {
                            // Una solicitud es N/A si NO tiene id_localidad en Propiedad Y NO tiene id_localidad en Referencia.
                            $q_null->whereNull('propiedades.id_localidad')
                                ->whereNull('solicitud_referencias.id_localidad');
                        });
                    }
                });
            }
        }

        if ($estatusQuery && !is_array($estatusQuery)) {
            $estatusQuery = [$estatusQuery];
        }

        if (!empty($estatusQuery)) {
            $solicitudesQuery->whereIn('id_estatus', $estatusQuery);
        }


        if ($sortColumn == "id_propietario") {
            $solicitudesQuery->orderBy('nombre_solicitante_propietario', $sortDirection);
        } else if ($sortColumn == 'id_tramite') {
            $solicitudesQuery->orderBy('tramites_nombres', $sortDirection);
        } else if ($sortColumn == 'id_estatus') {
            $solicitudesQuery->orderBy('estatus_solicitudes.nombre', $sortDirection)
                ->orderBy('solicitudes.fecha_ingreso', $sortDirection);
        } else if ($sortColumn == 'fecha_ingreso') {
            $solicitudesQuery->orderBy('solicitudes.fecha_ingreso', $sortDirection)
                ->orderBy('solicitudes.id', $sortDirection);
        } else if ($sortColumn == 'clave_catastral') {
            $solicitudesQuery->orderBy('propiedades.clave_catastral', $sortDirection);
        } else if ($sortColumn == 'id_localidad') {
            $solicitudesQuery->orderBy('nombre_localidad_prioritario', $sortDirection);
        } else if ($sortColumn == 'folio') {
            $solicitudesQuery->orderByRaw('solicitudes.folio IS NULL');
            $solicitudesQuery->orderBy('solicitudes.folio', $sortDirection);
        } else {
            $solicitudesQuery->orderBy($sortColumn, $sortDirection);
        }

        return [
            'query' => $solicitudesQuery,
            'ids_previos' => $solicitudesFiltradasIdsPrev
        ];
    }

    public function obtenerTramites($filtrarPorTipo = false, $solicitudesFiltradasIds, $tiposTramitesQuery, $estatusQuery, $localidadesQueryFiltradas)
    {
        $query = CatalogoTramite::where('activo', 1)->orderBy('nombre');

        if ($filtrarPorTipo && !empty($tiposTramitesQuery)) {
            $query->whereIn('id_tipo', $tiposTramitesQuery);
        }

        return $query->get()->map(function ($tramite) use ($solicitudesFiltradasIds) {
            $conteo = DB::table('solicitudes_tramites')
                ->where('id_tramite', $tramite->id)
                ->whereIn('id_solicitud', $solicitudesFiltradasIds)
                ->count();

            return [
                'id' => $tramite->id,
                'nombre' => $tramite->nombre,
                'count' => $conteo,
            ];
        });
    }

    public function obtenerTramitesPrev($filtrarPorTipo = false, $solicitudesFiltradasIdsPrev, $tiposTramitesQuery, $estatusQuery)
    {
        $query = CatalogoTramite::where('activo', 1)->orderBy('nombre');

        if ($filtrarPorTipo && !empty($tiposTramitesQuery)) {
            $query->whereIn('id_tipo', $tiposTramitesQuery);
        }

        return $query->get()->map(function ($tramite) use ($solicitudesFiltradasIdsPrev) {
            $conteo = DB::table('solicitudes_tramites')
                ->where('id_tramite', $tramite->id)
                ->whereIn('id_solicitud', $solicitudesFiltradasIdsPrev)
                ->count();

            return [
                'id' => $tramite->id,
                'nombre' => $tramite->nombre,
                'count' => $conteo,
            ];
        });
    }

    public function obtenerEstatus($solicitudesFiltradasIds)
    {
        return EstatusSolicitud::where('activo', true)
            ->get()
            ->map(function ($estatus) use ($solicitudesFiltradasIds) {
                $conteo = DB::table('solicitudes')
                    ->where('id_estatus', $estatus->id)
                    ->whereIn('id', $solicitudesFiltradasIds)
                    ->count();

                return [
                    'id' => $estatus->id,
                    'nombre' => $estatus->nombre,
                    'color' => $estatus->color,
                    'count' => $conteo,
                ];
            });
    }

    public function contarLocalidades($solicitudesFiltradasIds)
    {
        $idsAFiltrar = is_array($solicitudesFiltradasIds)
            ? $solicitudesFiltradasIds
            : $solicitudesFiltradasIds->toArray();

        if (empty($idsAFiltrar)) {
            return collect([]);
        }

        // 1. Crear una subconsulta de Solicitudes Filtradas
        $solicitudesFiltradas = DB::table('solicitudes')
            ->whereIn('id', $idsAFiltrar)
            ->select('id', 'id_propiedad');

        // 2. Consulta Principal de Agregación
        $conteoPorLocalidad = DB::table($solicitudesFiltradas, 's')

            // Uniones
            ->leftJoin('propiedades AS p', 's.id_propiedad', '=', 'p.id')
            ->leftJoin('solicitud_referencias AS sr', 's.id', '=', 'sr.id_solicitud')

            // 1. SELECT: Usar -1 para la agrupación 'N/A'
            ->select(
                // COALESCE(p.id_localidad, sr.id_localidad) obtendrá el ID real (incluyendo el 0).
                // Si el resultado es NULL (sin localidad), usamos -1 para el grupo 'N/A'.
                DB::raw("COALESCE(p.id_localidad, sr.id_localidad, -1) as localidad_id_unificado"),
                DB::raw("COUNT(DISTINCT s.id) as count")
            )

            // Agrupar por el ID unificado
            ->groupBy('localidad_id_unificado')
            ->get();

        // 3. Obtener los nombres de las localidades reales (incluyendo id = 0)
        $localidadIds = $conteoPorLocalidad->pluck('localidad_id_unificado')->filter(fn($id) => $id != -1)->unique()->toArray();

        $localidadesReales = DB::table('localidades')
            ->whereIn('id', $localidadIds)
            ->pluck('nombre', 'id');

        // 4. Formatear la salida y manejar 'N/A'
        return $conteoPorLocalidad->map(function ($item) use ($localidadesReales) {
            $id = $item->localidad_id_unificado;

            // Si el ID es -1, es 'N/A'.
            if ($id == -1) {
                return [
                    'id' => null,
                    'nombre' => 'N/A',
                    'count' => (int)$item->count,
                ];
            }

            // Si tiene un ID real (0 o positivo), usamos el nombre real.
            return [
                'id' => (int)$id,
                'nombre' => $localidadesReales->get($id, 'Localidad Desconocida'),
                'count' => (int)$item->count,
            ];
        })->filter(fn($item) => $item['count'] > 0);
    }


    public function obtenerEstatusPrev($solicitudesFiltradasIdsPrev)
    {
        return EstatusSolicitud::where('activo', true)
            ->get()
            ->map(function ($estatus) use ($solicitudesFiltradasIdsPrev) {
                $conteo = DB::table('solicitudes')
                    ->where('id_estatus', $estatus->id)
                    ->whereIn('id', $solicitudesFiltradasIdsPrev)
                    ->count();

                return [
                    'id' => $estatus->id,
                    'nombre' => $estatus->nombre,
                    'color' => $estatus->color,
                    'count' => $conteo,
                ];
            });
    }

    public function contarSinTramites($solicitudesFiltradasIds)
    {
        return DB::table('solicitudes')
            ->whereIn('id', $solicitudesFiltradasIds)
            ->whereNotIn('id', function ($query) {
                $query->select('id_solicitud')->from('solicitudes_tramites');
            })
            ->count();
    }

    public function contarSinTramitesPrev($solicitudesFiltradasIdsPrev)
    {
        return DB::table('solicitudes')
            ->whereIn('id', $solicitudesFiltradasIdsPrev)
            ->whereNotIn('id', function ($query) {
                $query->select('id_solicitud')->from('solicitudes_tramites');
            })
            ->count();
    }

    public function obtenerResumenSolicitudes(array $params): array
    {
        extract($params); // Extrae variables como $filtroChkSolicitudes, $nombreQuery, etc.

        $tramites = collect();
        $estatusSolicitud = collect();
        $localidadesCount = collect();
        $solicitudesSinTramitesCount = 0;

        $usarFiltro = !empty($tiposTramitesQuery);
        $usarNum = !empty($numQuery);
        $usarFolio = !empty($folioQuery);
        $usarNombre = !empty($nombreQuery);
        $usarClaveCatastral = !empty($claveCatastralQuery);
        $usarLocalidad = !empty($localidadesQueryFiltradas);
        $usarAmbosFiltros = !empty($tramitesQuery) && !empty($estatusQuery);
        $usarFechas = !is_null($fechaIngresoInicioQuery) && !is_null($fechaIngresoFinQuery);
        $usarFechasAceptacion = !is_null($fechaAceptacionInicioQuery) && !is_null($fechaAceptacionFinQuery);

        $getTramites = fn($prev = false, $filtrar = false) =>
        $prev
            ? $this->obtenerTramites(false, $solicitudesFiltradasIdsPrev, $tiposTramitesQuery, $filtrar, $localidadesQueryFiltradas)
            : $this->obtenerTramites(false, $solicitudesFiltradasIds, $tiposTramitesQuery, $filtrar, $localidadesQueryFiltradas);

        $getEstatus = fn($prev = false) =>
        $prev
            ? $this->obtenerEstatus($solicitudesFiltradasIdsPrev)
            : $this->obtenerEstatus($solicitudesFiltradasIds);

        $getSinTramites = fn($prev = false) =>
        $prev
            ? $this->contarSinTramites($solicitudesFiltradasIdsPrev)
            : $this->contarSinTramites($solicitudesFiltradasIds);

        $getLocalidades = fn($prev = false) =>
        $prev
            ? $this->contarLocalidades($solicitudesFiltradasIdsPrev)
            : $this->contarLocalidades($solicitudesFiltradasIds);

        switch ($filtroChkSolicitudes) {
            case 0:
                if ($usarNum || $usarNombre || $usarFiltro || $usarFechas || $usarFolio || $usarClaveCatastral || $usarFechasAceptacion) {
                    if ($usarLocalidad) {
                        $tramites = $getTramites(false, $usarFiltro);
                        $estatusSolicitud = $getEstatus(false);
                        $solicitudesSinTramitesCount = $getSinTramites(false);
                        $localidadesQuery = $getLocalidades(true);
                    } else {
                        $tramites = $getTramites(true, $usarFiltro);
                        $estatusSolicitud = $getEstatus(true);
                        $solicitudesSinTramitesCount = $getSinTramites(true);
                        $localidadesQuery = $getLocalidades(false);
                    }
                } else if ($usarLocalidad) {
                    $tramites = $getTramites(false, $usarFiltro);
                    $estatusSolicitud = $getEstatus(false);
                    $solicitudesSinTramitesCount = $getSinTramites(true);
                    $localidadesQuery = $getLocalidades(true);
                } else {
                    $tramites = CatalogoTramite::where('activo', 1)
                        ->orderBy('nombre')
                        ->withCount('solicitudesTramites')
                        ->get()
                        ->map(fn($t) => [
                            'id' => $t->id,
                            'nombre' => $t->nombre,
                            'count' => $t->solicitudes_tramites_count,
                        ]);

                    $estatusSolicitud = empty($tramitesQuery)
                        ? EstatusSolicitud::where('activo', true)
                        ->withCount('solicitudes')
                        ->get()
                        ->map(fn($e) => [
                            'id' => $e->id,
                            'nombre' => $e->nombre,
                            'color' => $e->color,
                            'count' => $e->solicitudes_count,
                        ])
                        : $getEstatus(false);

                    $solicitudesSinTramitesCount = $getSinTramites(false);

                    $localidadesQuery = $getLocalidades(false);
                }
                break;

            case 1:
                $tramites = $getTramites(true, $usarFiltro);
                $estatusSolicitud = $getEstatus(false);
                $solicitudesSinTramitesCount = $getSinTramites(true);
                $localidadesQuery = $getLocalidades(false);

                if ($usarAmbosFiltros) {
                    $tramites = $getTramites(true, $usarFiltro);
                    $solicitudesSinTramitesCount = $getSinTramites(false);
                }
                break;

            case 2:
                $estatusSolicitud = $getEstatus(true);
                $tramites = $getTramites(false, $usarFiltro);
                $solicitudesSinTramitesCount = $getSinTramites(false);
                $localidadesQuery = $getLocalidades(false);

                if ($usarAmbosFiltros) {
                    $estatusSolicitud = $getEstatus(true);
                    $solicitudesSinTramitesCount = $getSinTramites(false);
                }
                break;
        }

        if ($solicitudesSinTramitesCount > 0) {
            $tramites->push([
                'id' => null,
                'nombre' => 'SIN TRÁMITES',
                'count' => $solicitudesSinTramitesCount,
            ]);
        }

        return compact('tramites', 'estatusSolicitud', 'solicitudesSinTramitesCount', 'localidadesQuery');
    }

    public function index(Request $request)
    {
        $idUsuario = Auth::id();
        $config = ConfiguracionUsuario::where('id_user', $idUsuario)->first();

        $fechaIngresoInicioQuery =  $request->input('fechaIngresoInicioQuery');
        $fechaIngresoFinQuery =  $request->input('fechaIngresoFinQuery');
        $fechaAceptacionInicioQuery = $request->input('fechaAceptacionInicioQuery');
        $fechaAceptacionFinQuery =  $request->input('fechaAceptacionFinQuery');

        if ($config) {
            $rangoFechasIngresoManual = filter_var($request->input('rangoFechasIngresoManual'), FILTER_VALIDATE_BOOLEAN);

            if ($rangoFechasIngresoManual) {
                if (empty($fechaAceptacionInicioQuery) || is_null($fechaAceptacionInicioQuery)) {
                    $idRangoFechasIngresoQuery = 99;

                    $config->id_rango_fecha_busqueda = $idRangoFechasIngresoQuery;
                    $config->save();
                } else {
                    $fechaIngresoInicioQuery =  null;
                    $fechaIngresoFinQuery =  null;
                }
            } else {
                if (empty($fechaAceptacionInicioQuery) || is_null($fechaAceptacionInicioQuery)) {
                    $idRangoFechasIngresoQuery = $request->input('idRangoFechasIngresoQuery') ?
                        $request->input('idRangoFechasIngresoQuery') :
                        $config->id_rango_fecha_busqueda;

                    $rangoFechaIngreso = $this->regresaRangoFecha($idRangoFechasIngresoQuery);

                    $fechaIngresoInicioQuery = $rangoFechaIngreso[0];
                    $fechaIngresoFinQuery = $rangoFechaIngreso[1];

                    $config->id_rango_fecha_busqueda = $idRangoFechasIngresoQuery;
                    $config->save();
                } else {
                    $fechaIngresoInicioQuery =  null;
                    $fechaIngresoFinQuery =  null;
                }
            }

            if (empty($fechaIngresoInicioQuery) || is_null($fechaIngresoInicioQuery)) {
                $rangoFechasAceptacionManual = filter_var($request->input('rangoFechasAceptacionManual'), FILTER_VALIDATE_BOOLEAN);

                if ($rangoFechasAceptacionManual) {
                    $fechaAceptacionInicioQuery =  $request->input('fechaAceptacionInicioQuery');
                    $fechaAceptacionFinQuery =  $request->input('fechaAceptacionFinQuery');
                    $idRangoFechasAceptacionQuery = 99;
                    $config->id_rango_fecha_busqueda = $idRangoFechasAceptacionQuery;
                    $config->save();
                } else {
                    $idRangoFechasAceptacionQuery = $request->input('idRangoFechasAceptacionQuery') ?
                        $request->input('idRangoFechasAceptacionQuery') :
                        $config->id_rango_fecha_busqueda;
                    $rangoFechaAceptacion = $this->regresaRangoFecha($idRangoFechasAceptacionQuery);
                    $fechaAceptacionInicioQuery = $rangoFechaAceptacion[0];
                    $fechaAceptacionFinQuery = $rangoFechaAceptacion[1];
                    $config->id_rango_fecha_busqueda = $idRangoFechasAceptacionQuery;
                    $config->save();
                }
            }
        } else {
            $fechaIngresoInicioQuery = Carbon::today()->toDateString();
            $fechaIngresoFinQuery = Carbon::today()->toDateString();

            $idRangoFechasIngresoQuery = 1;

            $fechaAceptacionInicioQuery = Carbon::today()->toDateString();
            $fechaAceptacionFinQuery = Carbon::today()->toDateString();

            $idRangoFechasAceptacionQuery = 1;

            ConfiguracionUsuario::create([
                'id_user' => $idUsuario,
                'id_rango_fecha_busqueda' => 1,
            ]);
        }

        $sortColumn = $request->input('sortColumn', 'fecha_ingreso'); // Columna de ordenación
        $sortDirection = $request->input('sortDirection', 'asc'); // Dirección de ordenación
        $paraNuevaSolicitud = $request->input('paraNuevaSolicitud', false); // Dirección de ordenación
        $numQuery = $request->input('numQuery', null); // O '' si prefieres cadena vacía
        $folioQuery = $request->input('folioQuery', null); // O '' si prefieres cadena vacía
        $nombreQuery = $request->input('nombreQuery', null); // O '' si prefieres cadena vacía
        $claveCatastralQuery = $request->input('claveCatastralQuery', null); // O '' si prefieres cadena vacía
        $tiposTramitesQuery = $request->input('tiposTramitesQuery', []); // Array vacío para selecciones múltiples
        $tramitesQuery = $request->input('tramitesQuery', []); // Array vacío para selecciones múltiples
        $localidadesQueryFiltradas = $request->input('localidadesQueryFiltradas', []); // Array vacío para selecciones múltiples
        $estatusQuery = $request->input('estatusQuery', null); // O [] si esperas un array de estatus
        $filtroChkSolicitudes = $request->input('filtroChkSolicitudes', null); // O [] si esperas un array de estatus
        $rangoFechasIngresoManual = $request->input('rangoFechasIngresoManual', null); // O [] si esperas un array de estatus
        $rangoFechasAceptacionManual = $request->input('rangoFechasAceptacionManual', null); // O [] si esperas un array de estatus
        $page = $request->input('page', 1);

        // Obtener el ID del periodo actual
        $periodoId = SettingsHelper::get('periodo_actual');

        // Usar el ID para buscar el objeto completo
        $periodoActual = Periodo::find($periodoId);

        if (empty($tramitesQuery) && empty($estatusQuery))  //Si no hay TRÁMITES ni ESTATUS en + FILTROS
        {
            $filtroChkSolicitudes = 0;
        }

        if ($tramitesQuery && empty($estatusQuery))  //Si está seleccionado solo ESTATUS en + FILTROS
        {
            $filtroChkSolicitudes = 1;
        }

        if (empty($tramitesQuery) && $estatusQuery)  //Si están seleccionados TRÁMITES en + FILTROS
        {
            $filtroChkSolicitudes = 2;
        }

        $filtros = [
            'fechaIngresoInicioQuery' => $fechaIngresoInicioQuery,
            'fechaIngresoFinQuery' => $fechaIngresoFinQuery,
            'fechaAceptacionInicioQuery' => $fechaAceptacionInicioQuery,
            'fechaAceptacionFinQuery' => $fechaAceptacionFinQuery,
            'numQuery' => $numQuery,
            'folioQuery' => $folioQuery,
            'nombreQuery' => $nombreQuery,
            'claveCatastralQuery' => $claveCatastralQuery,
            'tiposTramitesQuery' => $tiposTramitesQuery,
            'tramitesQuery' => $tramitesQuery,
            'localidadesQueryFiltradas' => $localidadesQueryFiltradas,
            'estatusQuery' => $estatusQuery,
            'filtroChkSolicitudes' => $filtroChkSolicitudes !== null ? (int) $filtroChkSolicitudes : null,
            'rangoFechasIngresoManual' => $rangoFechasIngresoManual,
            'rangoFechasAceptacionManual' => $rangoFechasAceptacionManual,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'periodoActual' => $periodoActual,
            'paraNuevaSolicitud' => $paraNuevaSolicitud,
            'page' => $page,
        ];

        $props = $this->prepararVistaSolicitudes($filtros);

        $props['filters'] = $filtros;

        return Inertia::render('Solicitudes/Index', $props);
    }

    private function decodeToken(string $obfuscatedToken): string
    {
        // Divide el token ofuscado en un array de caracteres
        $chars = str_split($obfuscatedToken);

        // Mapea cada carácter usando el mapa de desobfuscación
        $decodedChars = array_map(function ($char) {
            // Si el carácter está en el mapa, devuelve su valor original; de lo contrario, devuelve el carácter tal cual.
            return $this->obfuscationDecodeMap[$char] ?? $char;
        }, $chars);

        // Une los caracteres decodificados de nuevo en una cadena
        return implode('', $decodedChars);
    }

    public function view(Request $request, $folioDigital)
    {
        $solicitud = Solicitud::where('folio_digital', $folioDigital)->firstOrFail();
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        // Usuarios del sistema: acceso directo
        if ($user && $user->hasRole('ver-solicitudes')) {
            $solicitud = Solicitud::with([
                'contacto',
                'propiedad',
                'estatus',
                'destino_obra',
                'tramites',
                'tramites.tramite.tipoTramite',
                'contacto.persona',
                'propiedad.contacto.persona',
                'propiedad.tipo',
                'propiedad.localidad',
                'propiedad.colonia',
                'referencia'
            ])->where('folio_digital', $folioDigital)->firstOrFail();

            $archivo = $solicitud->propiedad->img_croquis;
            $croquis = asset('storage/croquis/' . $archivo);

            return Inertia::render('Solicitudes/View', [
                'solicitud' => $solicitud,
                'croquis' => $croquis,
            ]);
        }
        // Alternatively, if you use permissions:
        // if ($user && $user->can('ver-solicitudes'))
        // Ciudadano autenticado: requiere token
        if ($request->query('token')) $tokenIngresado = $this->decodeToken($request->query('token'));
        else $tokenIngresado = null;


        if (!$tokenIngresado) {
            // Mostrar formulario para capturar token
            return Inertia::render('Solicitudes/VerificaToken', [
                'folio_digital' => $folioDigital,
            ]);
        }

        if ($tokenIngresado !== $solicitud->token_acceso) {
            return Inertia::render('Solicitudes/VerificaToken', [
                'folio_digital' => $folioDigital,
                'initialError' => 'El token es incorrecto.',
            ]);
        }

        $solicitud = Solicitud::with([
            'contacto',
            'propiedad',
            'estatus',
            'destino_obra',
            'tramites',
            'tramites.tramite.tipoTramite',
            'contacto.persona',
            'propiedad.contacto.persona',
            'propiedad.tipo',
            'propiedad.localidad',
            'propiedad.colonia'
        ])->where('folio_digital', $folioDigital)->firstOrFail();

        $archivo = $solicitud->propiedad->img_croquis;
        $croquis = asset('storage/croquis/' . $archivo);

        return Inertia::render('Solicitudes/View', [
            'solicitud' => $solicitud,
            'croquis' => $croquis,
        ]);
    }

    public function deleteCroquis(Request $request, $idSolicitud)
    {
        $tramitesSeleccionados = $request->input('tramitesSeleccionados');
        if (is_string($tramitesSeleccionados) && $tramitesSeleccionados !== '') {
            $tramitesSeleccionadosArray = explode(',', $tramitesSeleccionados);
        } elseif (is_array($tramitesSeleccionados)) {
            $tramitesSeleccionadosArray = $tramitesSeleccionados;
        } else {
            $tramitesSeleccionadosArray = []; // Si no es string ni array, inicializa como array vacío
        }

        $validaPropiedad = true;
        if (in_array($this->ID_CONSTANCIA_UBICACION, $tramitesSeleccionadosArray)) {
            $validaPropiedad = false;
        }

        $solicitud = Solicitud::findOrFail($idSolicitud);

        if ($validaPropiedad) {
            $propiedad = $solicitud->propiedad;
            $imgCroquisPropiedad = $propiedad->img_croquis;
            $contactoPropietario = $propiedad->contacto;
            $imgCroquis = $imgCroquisPropiedad;
        } else {
            $croquisAux = CroquisAux::where('id_solicitud', $idSolicitud)->first();
            if ($croquisAux) {
                $imgCroquisAux = $croquisAux->img;
                $imgCroquis = $imgCroquisAux;
            }
        }

        try {
            DB::beginTransaction();

            if ($validaPropiedad) {
                if ($propiedad->editable) {
                    $archivo = $propiedad->img_croquis;
                    $propiedad->img_croquis = null;
                    $propiedad->save();
                } else {
                    if ($request->callePropiedad === 'null') {
                        $request->merge(['callePropiedad' => null]);
                    }

                    if ($request->numeroPropiedad === 'null') {
                        $request->merge(['numeroPropiedad' => null]);
                    }

                    if ($request->idColoniaPropiedad === 'null') {
                        $request->merge(['idColoniaPropiedad' => null]);
                    }

                    if ($request->idLocalidadPropiedad === 'null') {
                        $request->merge(['idLocalidadPropiedad' => null]);
                    }

                    if ($request->superficiePropiedad === 'null') {
                        $request->merge(['superficiePropiedad' => null]);
                    }

                    if ($request->superficieConstruccionPropiedad === 'null') {
                        $request->merge(['superficieConstruccionPropiedad' => null]);
                    }

                    if ($request->imgCroquisPropiedad === 'null') {
                        $request->merge(['imgCroquisPropiedad' => null]);
                    }

                    if ($request->tipoPropiedad === 'null') {
                        $request->merge(['tipoPropiedad' => null]);
                    }

                    $propiedad = $this->regresaPropiedadActiva($request, $contactoPropietario);

                    $solicitud->id_propiedad = $propiedad->id;
                    $solicitud->save();
                }
            } else {
                $archivo = $croquisAux->img;
                $croquisAux->img = null;
                $croquisAux->save();
            }

            // Verificar si el archivo existe
            if (Storage::disk('public')->exists('croquis/' . $archivo)) {
                Storage::disk('public')->delete('croquis/' . $archivo);
            }

            // Eliminar el archivo
            DB::commit();

            $sortColumn = $request->input('sortColumn', 'id'); // Columna de ordenación
            $sortDirection = $request->input('sortDirection', 'asc'); // Dirección de ordenación

            $numQuery = $request->input('numQuery', null); // O '' si prefieres cadena vacía
            $folioQuery = $request->input('folioQuery', null); // O '' si prefieres cadena vacía
            $nombreQuery = $request->input('nombreQuery', null); // O '' si prefieres cadena vacía
            $claveCatastralQuery = $request->input('claveCatastralQuery', null); // O '' si prefieres cadena vacía
            $tiposTramitesQuery = $request->input('tiposTramitesQuery', []); // Array vacío para selecciones múltiples
            $tramitesQuery = $request->input('tramitesQuery', []); // Array vacío para selecciones múltiples
            $estatusQuery = $request->input('estatusQuery', null); // O [] si esperas un array de estatus
            $fechaIngresoInicioQuery =  $request->input('fechaIngresoInicioQuery');
            $fechaIngresoFinQuery =  $request->input('fechaIngresoFinQuery');
            $rangoFechasIngresoManual = $request->input('rangoFechasIngresoManual');
            $fechaAceptacionInicioQuery =  $request->input('fechaAceptacionInicioQuery');
            $fechaAceptacionFinQuery =  $request->input('fechaAceptacionFinQuery');
            $rangoFechasAceptacionManual = $request->input('rangoFechasAceptacionManual');
            $page = $request->input('page', 1);

            $filtros = [
                'fechaIngresoInicioQuery' => $fechaIngresoInicioQuery,
                'fechaIngresoFinQuery' => $fechaIngresoFinQuery,
                'fechaAceptacionInicioQuery' => $fechaAceptacionInicioQuery,
                'fechaAceptacionFinQuery' => $fechaAceptacionFinQuery,
                'numQuery' => $numQuery,
                'folioQuery' => $folioQuery,
                'nombreQuery' => $nombreQuery,
                'claveCatastralQuery' => $claveCatastralQuery,
                'tiposTramitesQuery' => $tiposTramitesQuery,
                'tramitesQuery' => $tramitesQuery,
                'estatusQuery' => $estatusQuery,
                'sortColumn' => $sortColumn,
                'sortDirection' => $sortDirection,
                'rangoFechasIngresoManual' => $rangoFechasIngresoManual,
                'rangoFechasAceptacionManual' => $rangoFechasAceptacionManual,
                'page' => $page,
            ];

            return redirect()->route('solicitudes', $filtros)->with('success', 'La imagen ha sido borrada con éxito!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('solicitudes')->with('error', 'Ocurrió un error al eliminar el croquis: ' . $e->getMessage());
        }

        $fechaIngresoInicioQuery =  $request->input('fechaIngresoInicioQuery');
        $fechaIngresoFinQuery =  $request->input('fechaIngresoFinQuery');
        $fechaAceptacionInicioQuery =  $request->input('fechaAceptacionInicioQuery');
        $fechaAceptacionFinQuery =  $request->input('fechaAceptacionFinQuery');
        $sortColumn = $request->input('sortColumn', 'id'); // Columna de ordenación
        $sortDirection = $request->input('sortDirection', 'asc'); // Dirección de ordenación
        $numQuery = $request->input('numQuery', null); // O '' si prefieres cadena vacía
        $folioQuery = $request->input('folioQuery', null); // O '' si prefieres cadena vacía
        $nombreQuery = $request->input('nombreQuery', null); // O '' si prefieres cadena vacía
        $claveCatastralQuery = $request->input('claveCatastralQuery', null); // O '' si prefieres cadena vacía
        $tiposTramitesQuery = $request->input('tiposTramitesQuery', []); // Array vacío para selecciones múltiples
        $tramitesQuery = $request->input('tramitesQuery', []); // Array vacío para selecciones múltiples
        $estatusQuery = $request->input('estatusQuery', null); // O [] si esperas un array de estatus

        $filtros = [
            'fechaIngresoInicioQuery' => $fechaIngresoInicioQuery,
            'fechaIngresoFinQuery' => $fechaIngresoFinQuery,
            'fechaAceptacionInicioQuery' => $fechaAceptacionInicioQuery,
            'fechaAceptacionFinQuery' => $fechaAceptacionFinQuery,
            'numQuery' => $numQuery,
            'folioQuery' => $folioQuery,
            'nombreQuery' => $nombreQuery,
            'claveCatastralQuery' => $claveCatastralQuery,
            'tiposTramitesQuery' => $tiposTramitesQuery,
            'tramitesQuery' => $tramitesQuery,
            'estatusQuery' => $estatusQuery,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ];

        if (Storage::disk('public')->exists('croquis/' . $imgCroquis)) {
            if ($validaPropiedad) {
                $solicitud->load(['contacto', 'propiedad', 'propiedad.contacto']);
            } else {
                $solicitud->load(['contacto', 'croquis_aux']);
            }
            Storage::disk('public')->delete('croquis/' . $imgCroquis);

            //return Inertia::render('Solicitudes/Index', $props);
            return redirect()->route('solicitudes', $filtros)->with('success', 'Imagen borrada con éxito!');
        } else {
            return redirect()->route('solicitudes', $filtros)->with('error', 'No se pudo eliminar el archivo porque no existe!');
        }
    }

    public function uploadCroquis(Request $request, $idSolicitud)
    {
        $tramitesSeleccionados = $request->input('tramitesSeleccionados');
        if (is_string($tramitesSeleccionados) && $tramitesSeleccionados !== '') {
            $tramitesSeleccionadosArray = explode(',', $tramitesSeleccionados);
        } elseif (is_array($tramitesSeleccionados)) {
            $tramitesSeleccionadosArray = $tramitesSeleccionados;
        } else {
            $tramitesSeleccionadosArray = []; // Si no es string ni array, inicializa como array vacío
        }

        $validaPropiedad = true;
        if (in_array($this->ID_CONSTANCIA_UBICACION, $tramitesSeleccionadosArray)) {
            $validaPropiedad = false;
        }

        if ($request->hasFile('archivo')) {
            DB::beginTransaction();

            try {
                $archivo = $request->file('archivo');

                $idSolicitudCeros = str_pad($idSolicitud % 1000000, 6, '0', STR_PAD_LEFT);

                if ($validaPropiedad) {
                    $nombreArchivo = $request->claveCatastral . '_' . $idSolicitudCeros . '_' . Str::random(3);
                } else {
                    if ($request->curpSolicitante) {
                        $nombreArchivo = $request->curpSolicitante . '_' . $idSolicitudCeros . '_' . Str::random(3);
                    } else {
                        $nombreArchivo = '000000000000000000' . '_' . $idSolicitudCeros . '_' . Str::random(3);
                    }
                }

                $extension = $archivo->getClientOriginalExtension();
                $nombreArchivoCroquis = $nombreArchivo . '.' . $extension;

                $manager = new ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                $imagen = $manager->read($archivo->getPathname());
                $imagen->scale(height: 480);

                $contenido = match (strtolower($extension)) {
                    'png' => $imagen->toPng()->toString(),
                    'webp' => $imagen->toWebp()->toString(),
                    default => $imagen->toJpeg()->toString(),
                };

                Storage::disk('public')->put('croquis/' . $nombreArchivoCroquis, $contenido);

                $solicitud = Solicitud::findOrFail($idSolicitud);
                $solicitud->load(['contacto', 'propiedad', 'propiedad.contacto', 'croquis_aux']);

                if ($validaPropiedad)  //Si es un croquis de PROPIEDAD
                {
                    $propiedad = $solicitud->propiedad;

                    if ($propiedad) {
                        if ($propiedad->editable) {
                            $propiedad->img_croquis = $nombreArchivoCroquis;
                            $propiedad->save();
                        } else {
                            Propiedad::where('clave_catastral', trim($propiedad->clave_catastral))
                                ->update(['activa' => 0]);  //Se ponen inactivas todas las propiedades con la clave catastral

                            $nuevaPropiedad = $propiedad->replicate();
                            $nuevaPropiedad->img_croquis = $nombreArchivoCroquis;
                            $nuevaPropiedad->editable = 1;
                            $nuevaPropiedad->activa = 1;
                            $nuevaPropiedad->save();

                            $solicitud->id_propiedad = $nuevaPropiedad->id;
                            $solicitud->save();

                            $solicitud->load(['contacto', 'propiedad', 'propiedad.contacto', 'croquis_aux']);
                        }
                    } else {
                        Propiedad::findOrFail($request->idPropiedadSolicitud)->update([
                            'img_croquis' => $nombreArchivoCroquis,
                        ]);
                    }
                } else  //Si es un croquis de CONSTANCIA DE UBICACIÓN
                {
                    // $croquis = CroquisAux::where('id_solicitud', $idSolicitud)->first();
                    $croquis = $solicitud->croquis_aux;

                    if ($croquis) {
                        $croquis->img = $nombreArchivoCroquis;
                        $croquis->save();
                    } else {
                        CroquisAux::create([
                            'id_solicitud' => $idSolicitud,
                            'img' => $nombreArchivoCroquis,
                        ]);
                    }
                }

                DB::commit();

                $sortColumn = $request->input('sortColumn', 'id'); // Columna de ordenación
                $sortDirection = $request->input('sortDirection', 'asc'); // Dirección de ordenación

                $numQuery = $request->input('numQuery', null); // O '' si prefieres cadena vacía
                $folioQuery = $request->input('folioQuery', null); // O '' si prefieres cadena vacía
                $nombreQuery = $request->input('nombreQuery', null); // O '' si prefieres cadena vacía
                $claveCatastralQuery = $request->input('claveCatastralQuery', null); // O '' si prefieres cadena vacía
                $tiposTramitesQuery = $request->input('tiposTramitesQuery', []); // Array vacío para selecciones múltiples
                $tramitesQuery = $request->input('tramitesQuery', []); // Array vacío para selecciones múltiples
                $estatusQuery = $request->input('estatusQuery', null); // O [] si esperas un array de estatus
                $fechaIngresoInicioQuery =  $request->input('fechaIngresoInicioQuery');
                $fechaIngresoFinQuery =  $request->input('fechaIngresoFinQuery');
                $rangoFechasIngresoManual = $request->input('rangoFechasIngresoManual');
                $fechaAceptacionInicioQuery =  $request->input('fechaAceptacionInicioQuery');
                $fechaAceptacionFinQuery =  $request->input('fechaAceptacionFinQuery');
                $rangoFechasAceptacionManual = $request->input('rangoFechasAceptacionManual');

                $page = $request->input('page', 1);

                $filtros = [
                    'fechaIngresoInicioQuery' => $fechaIngresoInicioQuery,
                    'fechaIngresoFinQuery' => $fechaIngresoFinQuery,
                    'fechaAceptacionInicioQuery' => $fechaAceptacionInicioQuery,
                    'fechaAceptacionFinQuery' => $fechaAceptacionFinQuery,
                    'numQuery' => $numQuery,
                    'folioQuery' => $folioQuery,
                    'nombreQuery' => $nombreQuery,
                    'claveCatastralQuery' => $claveCatastralQuery,
                    'tiposTramitesQuery' => $tiposTramitesQuery,
                    'tramitesQuery' => $tramitesQuery,
                    'estatusQuery' => $estatusQuery,
                    'sortColumn' => $sortColumn,
                    'sortDirection' => $sortDirection,
                    'rangoFechasIngresoManual' => $rangoFechasIngresoManual,
                    'rangoFechasAceptacionManual' => $rangoFechasAceptacionManual,
                    'page' => $page
                ];

                return redirect()->route('solicitudes', $filtros)
                    ->with([
                        'success' => 'Croquis subido con éxito!',
                        'solicitud' => $solicitud // <- Asegúrate de que $solicitud es el objeto o array que quieres enviar
                    ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Error al guardar croquis: ' . $e->getMessage());

                return back()->with('error', 'Ocurrió un error al subir el croquis.');
            }
        }

        return back()->withErrors(['archivo' => 'No se subió ningún archivo']);
    }

    //CHECKPOINT: En la esquina superior derecha poner TRÁMITE cuando sea un solo trámite y TRÁMITES cuando sean más de un trámite OK
    //CHECKPOINT: Voy a validar los momentos en que los requisitos y los trámites se vuelven INEDITABLES  OK
    //CHECKPOINT: También ver lo de ACTIVOS/INACTIVOS ver su comportamiento  OK
    //CHECKPOINT: Agregar campo DESCRIPCIÓN tanto a catalogo_tramites como a requisitos_documentacion  OK
    //CHECKPOINT: En el INDEX de Requisitos falta el combo ORDENAR
    //CHECKPOINT: Falta agregar campo ORDEN en catalogo_requisitos_tramites

    public function validaSolicitud(Request $request, $idSolicitud = null)
    {
        $esCurpPropietarioInvalida = filter_var($request->curpPropietarioInvalida, FILTER_VALIDATE_BOOLEAN);
        $esCurpSolicitanteInvalida = filter_var($request->curpSolicitanteInvalida, FILTER_VALIDATE_BOOLEAN);
        $esSolicitante = filter_var($request->esSolicitante, FILTER_VALIDATE_BOOLEAN);

        if ($request->telefonoPropietario === 'null') {
            $request->merge(['telefonoPropietario' => null]);
        }

        if ($request->emailPropietario === 'null') {
            $request->merge(['emailPropietario' => null]);
        }

        if ($request->idDomicilioNotificacionPropietario === 'undefined') {
            $request->merge(['idDomicilioNotificacionPropietario' => null]);
        }

        if ($request->domicilioNotificacionPropietario === 'undefined') {
            $request->merge(['domicilioNotificacionPropietario' => null]);
        }

        if ($request->telefonoSolicitante === 'null') {
            $request->merge(['telefonoSolicitante' => null]);
        }

        if ($request->emailSolicitante === 'null') {
            $request->merge(['emailSolicitante' => null]);
        }

        if ($request->idDomicilioNotificacionSolicitante === 'undefined') {
            $request->merge(['idDomicilioNotificacionSolicitante' => null]);
        }

        if ($request->domicilioNotificacionSolicitante === 'undefined') {
            $request->merge(['domicilioNotificacionSolicitante' => null]);
        }

        if ($request->callePropiedad === 'null') {
            $request->merge(['callePropiedad' => null]);
        }

        if ($request->numeroPropiedad === 'null') {
            $request->merge(['numeroPropiedad' => null]);
        }

        if ($request->idColoniaPropiedad === 'null') {
            $request->merge(['idColoniaPropiedad' => null]);
        }

        if ($request->idLocalidadPropiedad === 'null') {
            $request->merge(['idLocalidadPropiedad' => null]);
        }

        if ($request->superficiePropiedad === 'null') {
            $request->merge(['superficiePropiedad' => null]);
        }

        if ($request->superficieConstruccionPropiedad === 'null') {
            $request->merge(['superficieConstruccionPropiedad' => null]);
        }

        if ($request->imgCroquisPropiedad === 'null') {
            $request->merge(['imgCroquisPropiedad' => null]);
        }

        if ($request->tipoPropiedad === 'null') {
            $request->merge(['tipoPropiedad' => null]);
        }

        if ($request->idPersonaPropietario === 'null') {
            $request->merge(['idPersonaPropietario' => null]);
        }

        if ($request->idPersonaSolicitante === 'null') {
            $request->merge(['idPersonaSolicitante' => null]);
        }

        if ($request->idPropiedadSolicitud === 'null') {
            $request->merge(['idPropiedadSolicitud' => null]);
        }

        if ($request->idPropiedadSolicitud === 'undefined') {
            $request->merge(['idPropiedadSolicitud' => null]);
        }

        if ($request->idDestinoObra === 'null') {
            $request->merge(['idDestinoObra' => null]);
        }

        if ($request->tipoPropiedadReferencia === 'null') {
            $request->merge(['tipoPropiedadReferencia' => null]);
        }

        if ($request->idLocalidadReferencia === 'null') {
            $request->merge(['idLocalidadReferencia' => null]);
        }

        if ($request->fechaIngresoInicioQuery === 'null') {
            $request->merge(['fechaIngresoInicioQuery' => null]);
        }

        if ($request->fechaIngresoFinQuery === 'null') {
            $request->merge(['fechaIngresoFinQuery' => null]);
        }

        if ($request->fechaAceptacionInicioQuery === 'null') {
            $request->merge(['fechaAceptacionInicioQuery' => null]);
        }

        if ($request->fechaAceptacionFinQuery === 'null') {
            $request->merge(['fechaAceptacionFinQuery' => null]);
        }

        $validaPropiedad = true;

        $tramitesSeleccionados = (array) $request->input('tramitesSeleccionados', []);

        if (in_array($this->ID_CONSTANCIA_UBICACION, $tramitesSeleccionados)) {
            $validaPropiedad = false;
        }

        if (empty($request->tramitesSeleccionados)) {
            $errors = new MessageBag(['tramitesSeleccionados' => ['Selecciona al menos un TRÁMITE para poder continuar.']]);

            $this->activeTab = 'tramite';

            return $errors;
        } else {
            if (in_array($this->ID_CONSTANCIA_UBICACION, $request->tramitesSeleccionados)) {
                if (trim($request->referencia) == '') {
                    $errors = new MessageBag(['referencia' => ['La INFORMACIÓN DE REFERENCIA es obligatoria.']]);

                    $this->activeTab = 'referencia';

                    return $errors;
                }
            }
        }

        if ($validaPropiedad) {
            if ($esCurpPropietarioInvalida) {
                $errors = new MessageBag(['curpPropietario' => ['La CURP del PROPIETARIO es inválida.']]);
                $this->activeTab = 'propietario';

                return $errors;
            }

            if ($request->claveCatastral === null) {
                $errors = new MessageBag(['claveCatastral' => ['La CLAVE CATASTRAL es obligatoria.']]);
                $this->activeTab = 'propiedad';

                return $errors;
            } else {
                $validator = Validator::make($request->all(), [
                    'claveCatastral' => 'digits:18',
                    'idEstatusSolicitud' => 'required',
                ], [
                    'claveCatastral.digits' => '<li> La CLAVE CATASTRAL está incompleta. </li>',
                    'idEstatusSolicitud.required' => '<li> El ESTATUS de la SOLICITUD es obligatorio. </li>',
                ]);

                if ($validator->fails()) {
                    $this->activeTab = 'propiedad';

                    return $validator; // Envía los errores a la vista
                }
            }

            $validator = Validator::make($request->all(), [
                'curpPropietario' => 'required|string|size:18',
                'nomPropietario' => 'required|string|max:30',
                'apePropietario' => 'required|string|max:40',
                'emailPropietario' => 'nullable|email',
                'domicilioNotificacionPropietario' => 'nullable|string|max:100',
            ], [
                'curpPropietario.required' => '<li> La CURP del PROPIETARIO es obligatoria. </li>',
                'curpPropietario.size' => '<li> La longitud de la CURP del PROPIETARIO debe ser 18 caracteres. </li>',
                'nomPropietario.required' => '<li> El campo NOMBRE del PROPIETARIO es obligatorio. </li>',
                'nomPropietario.string' => '<li> El NOMBRE del PROPIETARIO debe ser una cadena de texto válida. </li>',
                'nomPropietario.max' => '<li> El NOMBRE del PROPIETARIO no puede tener más de 30 caracteres. </li>',
                'apePropietario.required' => '<li> El campo APELLIDOS del PROPIETARIO es obligatorio. </li>',
                'apePropietario.string' => '<li> Los APELLIDOS del PROPIETARIO deben ser una cadena de texto válida. </li>',
                'apePropietario.max' => '<li> Los APELLIDOS del PROPIETARIO no pueden tener más de 40 caracteres. </li>',
                'emailPropietario.email' => '<li> El CORREO ELECTRÓNICO del PROPIETARIO debe tener un formato válido. </li>',
                'domicilioNotificacionPropietario.max' => '<li> El DOMICILIO deL PROPIETARIO no puede tener más de 100 caracteres. </li>',
            ]);

            if ($validator->fails()) {
                $this->activeTab = 'propietario';
                return $validator; // Envía los errores a la vista
            }

            if ($request->telefonoPropietario !== null && $request->telefonoPropietario !== "null" && !preg_match('/^\d{10}$/', $request->telefonoPropietario)) {
                $errors = new MessageBag(['telefonoPropietario' => ['El TELÉFONO del PROPIETARIO debe tener exactamente 10 dígitos.']]);
                $this->activeTab = 'propietario';

                return $errors;
            }
        }

        if (!$esSolicitante || !$validaPropiedad) {
            if ($request->curpSolicitante === null) {
                $errors = new MessageBag(['curpSolicitante' => ['La CURP del SOLICITANTE debes introducirla.']]);
                $this->activeTab = 'solicitante';

                return $errors;
            }

            if ($request->curpSolicitante && !$esCurpSolicitanteInvalida) {
                $validator = Validator::make($request->all(), [
                    'curpSolicitante' => 'required|string|size:18',
                    'nomSolicitante' => 'required|string|max:30',
                    'apeSolicitante' => 'required|string|max:40',
                    'emailSolicitante' => 'nullable|email',
                    'domicilioNotificacionSolicitante' => 'nullable|string|max:100',
                ], [
                    'curpSolicitante.required' => '<li> La CURP del SOLICITANTE es obligatoria. </li>',
                    'curpSolicitante.size' => '<li> La longitud de la CURP del SOLICITANTE debe ser 18 caracteres. </li>',
                    'nomSolicitante.required' => '<li> El campo NOMBRE del SOLICITANTE es obligatorio. </li>',
                    'nomSolicitante.string' => '<li> El NOMBRE del SOLICITANTE debe ser una cadena de texto válida. </li>',
                    'nomSolicitante.max' => '<li> El NOMBRE del SOLICITANTE no puede tener más de 30 caracteres. </li>',
                    'apeSolicitante.required' => '<li> El campo APELLIDOS del SOLICITANTE es obligatorio. </li>',
                    'apeSolicitante.string' => '<li> Los APELLIDOS del SOLICITANTE deben ser una cadena de texto válida. </li>',
                    'apeSolicitante.max' => '<li> Los APELLIDOS del SOLICITANTE no pueden tener más de 40 caracteres. </li>',
                    'emailSolicitante.email' => '<li> El CORREO ELECTRÓNICO del SOLICITANTE debe tener un formato válido. </li>',
                    'domicilioNotificacionSolicitante.max' => '<li> El DOMICILIO del SOLICITANTE no puede tener más de 100 caracteres. </li>',
                ]);

                if ($validator->fails()) {
                    $this->activeTab = 'solicitante';

                    return $validator; // Envía los errores a la vista
                }
            }
        }

        if ($validaPropiedad) {
            $validator = Validator::make($request->all(), [
                'superficiePropiedad' => 'nullable|numeric',
                'callePropiedad' => 'nullable|string|max:80',
                'numeroPropiedad' => 'nullable|string|max:8',
            ], [
                'superficiePropiedad.numeric' => '<li>La SUPERFICIE de la PROPIEDAD debe ser un número.</li>',

                'callePropiedad.string' => '<li>La CALLE de la PROPIEDAD debe ser texto.</li>',
                'callePropiedad.max' => '<li>La CALLE de la PROPIEDAD no debe exceder los 80 caracteres.</li>',
                'numeroPropiedad.string' => '<li>El NÚMERO de la PROPIEDAD debe ser texto.</li>',
                'numeroPropiedad.max' => '<li>El NÚMERO de la PROPIEDAD no debe exceder los 8 caracteres.</li>',
            ]);

            if ($validator->fails()) {
                $this->activeTab = 'propiedad';
                return $validator; // Envía los errores a la vista
            }

            if ($request->callePropiedad === null) {
                $request->merge(['callePropiedad' => '']);
            }

            if ($request->numeroPropiedad === null) {
                $request->merge(['numeroPropiedad' => '']);
            }
        }

        if ($request->idEstatusSolicitud == 99) {
            if ($validaPropiedad) {
                if (!$request->croquis)  //Esta validación es cuando se cargó el CROQUIS apenas antes de guardar
                {

                    if ($request->imgCroquisPropiedad === null) {
                        $this->activeTab = 'croquis';

                        $errors = new MessageBag(['croquis' => ['El CROQUIS es obligatorio.']]);
                        return $errors;
                    } else {
                        $this->activeTab = 'croquis';

                        $validator = Validator::make($request->all(), [
                            'imgCroquisPropiedad' => 'required',
                        ], [
                            'imgCroquisPropiedad.required' => '<li> El CROQUIS es obligatorio. </li>',
                        ]);

                        if ($validator->fails()) {
                            return $validator; // Envía los errores a la vista
                        }
                    }
                }

                $validator = Validator::make($request->all(), [
                    'tipoPropiedad' => 'required',
                    'superficiePropiedad' => 'required|numeric',
                    'callePropiedad' => 'required|string|max:80',
                    'numeroPropiedad' => 'string|max:8',
                    'idLocalidadPropiedad' => 'required',
                ], [
                    'tipoPropiedad.required' => '<li> El TIPO de PROPIEDAD es obligatorio. </li>',
                    'superficiePropiedad.required' => '<li>La SUPERFICIE de la PROPIEDAD es obligatoria.</li>',
                    'superficiePropiedad.numeric' => '<li>La SUPERFICIE de la PROPIEDAD debe ser un número.</li>',

                    'callePropiedad.required' => '<li>La CALLE de la PROPIEDAD es obligatoria.</li>',
                    'callePropiedad.string' => '<li>La CALLE de la PROPIEDAD debe ser texto.</li>',
                    'callePropiedad.max' => '<li>La CALLE de la PROPIEDAD no debe exceder los 80 caracteres.</li>',
                    // 'numeroPropiedad.required' => '<li>El NÚMERO de la PROPIEDAD es obligatorio.</li>',
                    'numeroPropiedad.string' => '<li>El NÚMERO de la PROPIEDAD debe ser texto.</li>',
                    'numeroPropiedad.max' => '<li>El NÚMERO de la PROPIEDAD no debe exceder los 8 caracteres.</li>',
                    'idLocalidadPropiedad.required' => '<li>La LOCALIDAD de la PROPIEDAD es obligatoria.</li>',
                ]);

                if ($validator->fails()) {
                    $this->activeTab = 'propiedad';
                    return $validator; // Envía los errores a la vista
                }

                if ($request->tipoPropiedad === '2') {
                    $validator = Validator::make($request->all(), [
                        'superficieConstruccionPropiedad' => 'required|numeric'
                    ], [

                        'superficieConstruccionPropiedad.required' => '<li>La SUPERFICIE de CONSTRUCCIÓN de la PROPIEDAD es obligatoria.</li>',
                        'superficieConstruccionPropiedad.numeric' => '<li>La SUPERFICIE de CONSTRUCCIÓN de la PROPIEDAD debe ser un número.</li>',
                    ]);

                    $this->activeTab = 'propiedad';

                    if ($validator->fails()) {
                        return $validator; // Envía los errores a la vista
                    }
                }

                $validator = Validator::make($request->all(), [
                    'telefonoPropietario' => 'required'
                ], [

                    'telefonoPropietario.required' => '<li>El TELÉFONO del PROPIETARIO es obligatorio.</li>',
                ]);

                if ($validator->fails()) {
                    $this->activeTab = 'propietario';

                    return $validator; // Envía los errores a la vista
                }
            } else {
                if ($request->imgCroquisAux === null) {
                    $imgCroquis = CroquisAux::where('id_solicitud', $idSolicitud)->first();
                    if ($imgCroquis === null) {
                        $this->activeTab = 'croquis';

                        $errors = new MessageBag(['croquis' => ['El CROQUIS es obligatorio.']]);
                        return $errors;
                    }
                }
            }

            if (!$esSolicitante) {
                $validator = Validator::make($request->all(), [
                    'telefonoSolicitante' => 'required'
                ], [

                    'telefonoSolicitante.required' => '<li>El TELÉFONO del SOLICITANTE es obligatorio.</li>',
                ]);

                if ($validator->fails()) {
                    $this->activeTab = 'solicitante';

                    return $validator; // Envía los errores a la vista
                }
            }

            if ($request->idDestinoObra === null) {
                $errors = new MessageBag(['idDestinoObra' => ['El DESTINO de OBRA es obligatorio.']]);

                $this->activeTab = 'tramite';

                return $errors;
            }
        }
    }

    public function updateEstatus(Request $request, $idSolicitud, $idEstatus)
    {
        // Usar findOrFail() para buscar la solicitud. Si no existe, lanza una excepción.
        try {
            $solicitud = Solicitud::findOrFail($idSolicitud);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Error: La solicitud principal no fue encontrada.');
        }

        // INICIO DE LA TRANSACCIÓN
        DB::beginTransaction();

        try {
            // 1. Actualizar el estatus de la solicitud principal
            $folio = $solicitud->folio;

            // Lógica condicional para SolicitudAceptada
            if ($idEstatus == 6) // Si el estatus es CANCELADA
            {
                // Buscar el registro por id_solicitud
                $solicitudAceptada = SolicitudAceptada::where('id', $folio)->first();

                if ($solicitudAceptada) {
                    // Eliminar el registro de aceptación (si existía)
                    $solicitudAceptada->activa = 0;
                }
            } elseif ($idEstatus == 99) // Si el estatus es ACEPTADA
            {
                // Buscar el registro por id_solicitud
                $solicitudAceptada = SolicitudAceptada::where('id', $folio)->first();

                if ($solicitudAceptada) {
                    $solicitudAceptada->activa = 1;
                }
            }

            $solicitud->id_estatus = $idEstatus;
            $solicitud->save();
            $solicitudAceptada->save();

            // Confirmar la Transacción
            DB::commit();

            $fechaIngresoInicioQuery = $request->input('fechaIngresoInicioQuery');
            $fechaIngresoFinQuery = $request->input('fechaIngresoFinQuery');
            $fechaAceptacionInicioQuery = $request->input('fechaAceptacionInicioQuery');
            $fechaAceptacionFinQuery = $request->input('fechaAceptacionFinQuery');
            $numQuery = $request->input('numQuery');
            $folioQuery = $request->input('folioQuery');
            $nombreQuery = $request->input('nombreQuery');
            $claveCatastralQuery = $request->input('claveCatastralQuery');
            // Para filtros que son arrays (ej. selects múltiples), proporciona un array vacío.
            $tiposTramitesQuery = $request->input('tiposTramitesQuery', []);
            $localidadesQueryFiltradas = $request->input('localidadesQueryFiltradas', []);
            $tramitesQuery = $request->input('tramitesQuery', []);
            $estatusQuery = $request->input('estatusQuery', []);
            $filtroChkSolicitudes = $request->input('filtroChkSolicitudes');
            $sortColumn = $request->input('sortColumn', 'id'); // Valor por defecto para la columna de orden
            $sortDirection = $request->input('sortDirection', 'asc'); // Valor por defecto para la dirección
            $periodoActual = $request->input('periodoActual');
            $rangoFechasIngresoManual = $request->input('rangoFechasIngresoManual');
            $rangoFechasAceptacionManual = $request->input('rangoFechasAceptacionManual');
            // El valor booleano 'paraNuevaSolicitud' no debe venir del request, sino ser una constante interna
            $page = $request->input('page'); // Página actual para la paginación

            // 1. Array de filtros para pasarlos a Inertia
            $filtros = [
                'fechaIngresoInicioQuery' => $fechaIngresoInicioQuery,
                'fechaIngresoFinQuery' => $fechaIngresoFinQuery,
                'fechaAceptacionInicioQuery' => $fechaAceptacionInicioQuery,
                'fechaAceptacionFinQuery' => $fechaAceptacionFinQuery,
                'numQuery' => $numQuery,
                'folioQuery' => $folioQuery,
                'nombreQuery' => $nombreQuery,
                'claveCatastralQuery' => $claveCatastralQuery,
                'tiposTramitesQuery' => $tiposTramitesQuery,
                'localidadesQueryFiltradas' => $localidadesQueryFiltradas,
                'tramitesQuery' => $tramitesQuery,
                'estatusQuery' => $estatusQuery,
                'filtroChkSolicitudes' => $filtroChkSolicitudes,
                'sortColumn' => $sortColumn,
                'sortDirection' => $sortDirection,
                'periodoActual' => $periodoActual,
                'rangoFechasIngresoManual' => $rangoFechasIngresoManual,
                'rangoFechasAceptacionManual' => $rangoFechasAceptacionManual,
                'paraNuevaSolicitud' => false, // Este valor se mantiene estático como lo solicitaste
                'page' => $page,
            ];

            return redirect()->route('solicitudes', $filtros)->with('success', 'Solicitud actualizada con éxito!');
        } catch (\Exception $e) {

            DB::rollback(); // REVERTIR LA TRANSACCIÓN EN CASO DE ERROR

            // Registrar el error para revisión
            logger()->error('Error fatal al actualizar estatus y registro SolicitudAceptada.', [
                'idSolicitud' => $idSolicitud,
                'idEstatus' => $idEstatus,
                'error' => $e->getMessage(),
            ]);

            // Retornar una respuesta de error
            return redirect()->back()->with('error', 'Error del sistema: No se pudo completar la operación. Intente nuevamente.');
        }
    }

    public function regresaPersonaPropietario(Request $request)
    {
        $personaPropietario = null;
        $esNueva = false;

        if ($request->idPersonaPropietario) {
            $personaPropietario = Persona::find($request->idPersonaPropietario);

            if ($personaPropietario && $personaPropietario->editable) {
                if ($personaPropietario->nombre != trim(mb_strtoupper($request->nomPropietario))) {
                    $personaPropietario->nombre = trim(mb_strtoupper($request->nomPropietario));
                }
                if ($personaPropietario->apellidos != trim(mb_strtoupper($request->apePropietario))) {
                    $personaPropietario->apellidos = trim(mb_strtoupper($request->apePropietario));
                }
                $personaPropietario->save();
            } elseif ($personaPropietario) {
                list($personaPropietario, $esNueva)  = $this->regresaPersonaPropietarioActiva($request);
            }
        }

        if (!$personaPropietario) {
            list($personaPropietario, $esNueva) = $this->regresaPersonaPropietarioActiva($request);
        }

        return [$personaPropietario, $esNueva];
    }

    public function regresaPersonaPropietarioActiva(Request $request)
    {
        $esNueva = false;

        $personaPropietario = Persona::where('curp', trim(mb_strtoupper($request->curpPropietario)))
            ->where('nombre', trim(mb_strtoupper($request->nomPropietario)))
            ->where('apellidos', trim(mb_strtoupper($request->apePropietario)))
            ->first();

        if ($personaPropietario)  //Si la hay y no es activa entonces la activa
        {
            if (!$personaPropietario->activa) {
                Persona::where('curp', $request->curpPropietario)
                    ->update(['activa' => 0]);  //Se ponen inactivas todas las personas con ese curp

                $personaPropietario->activa = 1;
                $personaPropietario->save();
            }
        } else  //Si no, entonces la crea
        {
            Persona::where('curp', $request->curpPropietario)
                ->update(['activa' => 0]);  //Se ponen inactivas todas las personas con ese curp

            $personaPropietario = Persona::create([
                'curp' => trim(mb_strtoupper($request->curpPropietario)),
                'nombre' => trim(mb_strtoupper($request->nomPropietario)),
                'apellidos' => trim(mb_strtoupper($request->apePropietario)),
            ]);

            $esNueva = true;
        }

        return [$personaPropietario, $esNueva];
    }

    public function regresaContactoPropietario(Request $request, $personaPropietario, $esPersonaNueva = false)
    {
        $contactoPropietario = $request->idContactoPropiedad ? Contacto::find($request->idContactoPropiedad) : null;

        if ($contactoPropietario) //Si ya existe
        {
            if ($contactoPropietario->editable) //Si se pueden editar los datos del propietario
            {   //Si hay cambios entonces actualiza el campo correspondiente
                if ($contactoPropietario->telefono != trim($request->telefonoPropietario)) {
                    $contactoPropietario->telefono = trim($request->telefonoPropietario);
                }
                if ($contactoPropietario->email != trim(mb_strtolower($request->emailPropietario))) {
                    $contactoPropietario->email = $request->emailPropietario !== null ? trim(mb_strtolower($request->emailPropietario)) : null;
                }
                if ($request->idDomicilioNotificacionPropietario) {
                    if ($request->domicilioNotificacionPropietario) {
                        $domicilioNotificacion = DomicilioNotificacion::find($request->idDomicilioNotificacionPropietario);
                        $domicilioNotificacion->direccion = $request->domicilioNotificacionPropietario ? trim(mb_strtoupper($request->domicilioNotificacionPropietario)) : '';
                        $domicilioNotificacion->save();
                    } else {
                        $contactoPropietario->id_domicilio = null;
                    }
                } else {
                    if ($request->domicilioNotificacionPropietario) {
                        $domicilioNotificacion = DomicilioNotificacion::create([
                            'direccion' => $request->domicilioNotificacionPropietario ? trim(mb_strtoupper($request->domicilioNotificacionPropietario)) : '',
                        ]);

                        $contactoPropietario->id_domicilio = $domicilioNotificacion->id;
                    }
                }
                $contactoPropietario->save();
            } else  //Si no se pueden editar los campos
            {
                $contactoPropietario = $this->regresaContactoPropietarioActivo($request, $personaPropietario, $esPersonaNueva);
            }
        } else  //Si no existe el contacto del propietario lo crea
        {
            $contactoPropietario = $this->regresaContactoPropietarioActivo($request, $personaPropietario, $esPersonaNueva);
        }

        return $contactoPropietario;
    }

    public function regresaContactoPropietarioActivo(Request $request, $personaPropietario, bool $esPersonaNueva = false)
    {
        if ($esPersonaNueva) {
            Contacto::where('id_persona', $personaPropietario->id)->update(['activo' => 0]);

            $idNotificacion = null;

            if (strlen($request->domicilioNotificacionPropietario) > 0) {
                $domicilioNotificacion = DomicilioNotificacion::create([
                    'direccion' => trim(mb_strtoupper($request->domicilioNotificacionPropietario)),
                ]);
                $idNotificacion = $domicilioNotificacion->id;
            }

            return Contacto::create([
                'id_persona' => $personaPropietario->id,
                'telefono' => trim($request->telefonoPropietario),
                'email' => $request->emailPropietario !== null ? trim(mb_strtolower($request->emailPropietario)) : null,
                'id_domicilio' => $idNotificacion,
                'activo' => true
            ]);
        }

        //Busca al propietario con el TELÉFONO y EMAIL
        if ((Str::startsWith(trim($request->telefonoPropietario), '0') || trim($request->telefonoPropietario) == '') && $request->emailPropietario === null) {
            $contactoPropietario = null;
        } else {
            $contactoPropietario = Contacto::where('telefono', trim($request->telefonoPropietario))
                ->when($request->emailPropietario !== null, function ($query) use ($request) {
                    $query->where('email', trim($request->emailPropietario));
                })
                ->when($request->curpPropietario !== null, function ($query) use ($request) {
                    $query->whereHas('persona', function ($q) use ($request) {
                        $q->where('curp', trim($request->curpPropietario));
                    });
                })
                ->first();
        }

        if ($contactoPropietario)  //Si existe entonces pregunta si es el activo
        {
            if ($contactoPropietario->activo)  //Si es activo
            {
                if (strlen($request->domicilioNotificacionPropietario) > 0) {
                    if ($contactoPropietario->id_domicilio) {
                        $domicilioNotificacion = DomicilioNotificacion::find($contactoPropietario->id_domicilio);
                        $domicilioNotificacion->direccion = $request->domicilioNotificacionPropietario ? trim(mb_strtoupper($request->domicilioNotificacionPropietario)) : '';
                        $domicilioNotificacion->save();
                    } else {
                        $domicilioNotificacion = DomicilioNotificacion::create([
                            'direccion' => trim(mb_strtoupper($request->domicilioNotificacionPropietario)),
                        ]);

                        $idNotificacion = $domicilioNotificacion->id;

                        if ($contactoPropietario->editable) {
                            $contactoPropietario->id_domicilio = $domicilioNotificacion->id;
                            $contactoPropietario->save();
                        } else {
                            Contacto::where('id_persona', $personaPropietario->id)
                                ->update(['activo' => 0]);  //Se ponen inactivas todos los contactos de la persona propietaria

                            $contactoPropietario = Contacto::create([
                                'id_persona' => $personaPropietario->id,
                                'telefono' => trim($request->telefonoPropietario),
                                'email' => $request->emailPropietario !== null ? trim(mb_strtolower($request->emailPropietario)) : null,
                                'id_domicilio' => $idNotificacion,
                                'activo' => true
                            ]);
                        }
                    }
                }
            } else {
                Contacto::where('id_persona', $personaPropietario->id)
                    ->update(['activo' => 0]);  //Se ponen inactivas todos los contactos de la persona propietaria

                $contactoPropietario->activo = 1;  //Si no está activa hay que activarla
                $contactoPropietario->save();
            }
        } else  //Si no existe es que se ha cambiado el TELÉFONO o el EMAIL
        {
            Contacto::where('id_persona', $personaPropietario->id)
                ->update(['activo' => 0]);  //Se ponen inactivas todos los contactos de la persona propietaria

            $idNotificacion = null;

            if (strlen($request->domicilioNotificacionPropietario) > 0) {
                $domicilioNotificacion = DomicilioNotificacion::create([
                    'direccion' => trim(mb_strtoupper($request->domicilioNotificacionPropietario)),
                ]);
                $idNotificacion = $domicilioNotificacion->id;
            }

            //Se crea un nuevo propietario, el cual será el ACTIVO  
            $contactoPropietario = Contacto::create([
                'id_persona' => $personaPropietario->id,
                'telefono' => trim($request->telefonoPropietario),
                'email' => $request->emailPropietario !== null ? trim(mb_strtolower($request->emailPropietario)) : null,
                'id_domicilio' => $idNotificacion,
            ]);
        }
        return $contactoPropietario;
    }

    public function regresaPersonaSolicitante(Request $request, $personaPropietario = null)
    {
        $esNueva = false;

        if ($personaPropietario) {
            $personaSolicitante = $personaPropietario;
        } else {
            $personaSolicitante = $request->idPersonaSolicitante ? Persona::find($request->idPersonaSolicitante) : null;

            if ($personaSolicitante)  //Si ya existe
            {
                if ($personaSolicitante->editable)  //Si se puede editar
                {   //Si hay cambios entonces actualiza el campo correspondiente
                    if ($personaSolicitante->nombre != trim(mb_strtoupper($request->nomSolicitante))) {
                        $personaSolicitante->nombre = trim(mb_strtoupper($request->nomSolicitante));
                    }
                    if ($personaSolicitante->apellidos != trim(mb_strtoupper($request->apeSolicitante))) {
                        $personaSolicitante->apellidos = trim(mb_strtoupper($request->apeSolicitante));
                    }
                    $personaSolicitante->save();
                } else {
                    list($personaSolicitante, $esNueva) = $this->regresaPersonaSolicitanteActiva($request);
                }
            } else  //Si no existe la persona busca o agrega la persona activa
            {
                list($personaSolicitante, $esNueva) = $this->regresaPersonaSolicitanteActiva($request);
            }
        }

        return [$personaSolicitante, $esNueva];
    }

    public function regresaPersonaSolicitanteActiva(Request $request)
    {
        $esNueva = false;

        $personaSolicitante = Persona::where('curp', trim(mb_strtoupper($request->curpSolicitante)))
            ->where('nombre', trim(mb_strtoupper($request->nomSolicitante)))
            ->where('apellidos', trim(mb_strtoupper($request->apeSolicitante)))
            ->first();

        if ($personaSolicitante)  //Si la hay y no es activa entonces la activa
        {
            if (!$personaSolicitante->activa) {
                Persona::where('curp', $request->curpSolicitante)
                    ->update(['activa' => 0]);  //Se ponen inactivas todas las personas con ese curp

                $personaSolicitante->activa = 1;
                $personaSolicitante->save();
            }
        } else  //Si no, entonces la crea
        {
            Persona::where('curp', $request->curpSolicitante)
                ->update(['activa' => 0]);  //Se ponen inactivas todas las personas con ese curp

            $personaSolicitante = Persona::create([
                'curp' => trim(mb_strtoupper($request->curpSolicitante)),
                'nombre' => trim(mb_strtoupper($request->nomSolicitante)),
                'apellidos' => trim(mb_strtoupper($request->apeSolicitante)),
            ]);

            $esNueva = true;
        }

        return [$personaSolicitante, $esNueva];
    }

    public function regresaContactoSolicitante(Request $request, $personaSolicitante, $contactoPropietario = null, $esPersonaNueva = false)
    {
        if ($contactoPropietario) {
            $contactoSolicitante = $contactoPropietario;
        } else {
            $contactoSolicitante = $request->idContactoSolicitud ? Contacto::find($request->idContactoSolicitud) : null;

            if ($contactoSolicitante) //Si ya existe
            {
                if ($contactoSolicitante->editable) //Si se pueden editar los datos del solicitante
                {   //Si hay cambios entonces actualiza el campo correspondiente
                    if ($contactoSolicitante->telefono != trim($request->telefonoSolicitante)) {
                        $contactoSolicitante->telefono = trim($request->telefonoSolicitante);
                    }
                    if ($contactoSolicitante->email != trim(mb_strtolower($request->emailSolicitante))) {
                        $contactoSolicitante->email = $request->emailSolicitante !== null ? trim(mb_strtolower($request->emailSolicitante)) : null;
                    }
                    if ($request->idDomicilioNotificacionSolicitante) {
                        if ($request->domicilioNotificacionSolicitante) {
                            $domicilioNotificacion = DomicilioNotificacion::find($request->idDomicilioNotificacionSolicitante);
                            $domicilioNotificacion->direccion = $request->domicilioNotificacionSolicitante ? trim(mb_strtoupper($request->domicilioNotificacionSolicitante)) : '';
                            $domicilioNotificacion->save();
                        } else {
                            $contactoSolicitante->id_domicilio = null;
                        }
                    } else {
                        if ($request->domicilioNotificacionSolicitante) {
                            $domicilioNotificacion = DomicilioNotificacion::create([
                                'direccion' => $request->domicilioNotificacionSolicitante ? trim(mb_strtoupper($request->domicilioNotificacionSolicitante)) : '',
                            ]);
                            $contactoSolicitante->id_domicilio = $domicilioNotificacion->id;
                        } else {
                            $contactoSolicitante->id_domicilio = null;
                        }
                    }
                    $contactoSolicitante->save();
                } else  //Si no se pueden editar los campos
                {
                    $contactoSolicitante = $this->regresaContactoSolicitanteActivo($request, $personaSolicitante, $esPersonaNueva);
                }
            } else  //Si no existe el contacto del solicitante lo crea
            {
                $contactoSolicitante = $this->regresaContactoSolicitanteActivo($request, $personaSolicitante, $esPersonaNueva);
            }
        }
        return $contactoSolicitante;
    }

    public function regresaContactoSolicitanteActivo(Request $request, $personaSolicitante, bool $esPersonaNueva = false)
    {
        if ($esPersonaNueva) {
            Contacto::where('id_persona', $personaSolicitante->id)->update(['activo' => 0]);

            $idNotificacion = null;

            if (strlen($request->domicilioNotificacionSolicitante) > 0) {
                $domicilioNotificacion = DomicilioNotificacion::create([
                    'direccion' => trim(mb_strtoupper($request->domicilioNotificacionSolicitante)),
                ]);
                $idNotificacion = $domicilioNotificacion->id;
            }

            return Contacto::create([
                'id_persona' => $personaSolicitante->id,
                'telefono' => trim($request->telefonoSolicitante),
                'email' => $request->emailSolicitante !== null ? trim(mb_strtolower($request->emailSolicitante)) : null,
                'id_domicilio' => $idNotificacion,
                'activo' => true
            ]);
        }

        //Busca al propietario con el TELÉFONO y EMAIL
        if ((Str::startsWith(trim($request->telefonoSolicitante), '0') || trim($request->telefonoSolicitante) == '') && $request->emailSolicitante === null) {
            $contactoSolicitante = null;
        } else {
            $contactoSolicitante = Contacto::where('telefono', trim($request->telefonoSolicitante))
                ->when($request->emailSolicitante !== null, function ($query) use ($request) {
                    $query->where('email', trim($request->emailSolicitante));
                })
                ->when($request->curpSolicitante !== null, function ($query) use ($request) {
                    $query->whereHas('persona', function ($q) use ($request) {
                        $q->where('curp', trim($request->curpSolicitante));
                    });
                })
                ->first();
        }

        if ($contactoSolicitante)  //Si existe entonces pregunta si es el activo
        {
            if ($contactoSolicitante->activo) {
                if (strlen($request->domicilioNotificacionSolicitante) > 0) {
                    if ($contactoSolicitante->id_domicilio) {
                        $domicilioNotificacion = DomicilioNotificacion::find($contactoSolicitante->id_domicilio);
                        $domicilioNotificacion->direccion = $request->domicilioNotificacionSolicitante ? trim(mb_strtoupper($request->domicilioNotificacionSolicitante)) : '';
                        $domicilioNotificacion->save();
                    } else {
                        $domicilioNotificacion = DomicilioNotificacion::create([
                            'direccion' => trim(mb_strtoupper($request->domicilioNotificacionSolicitante)),
                        ]);

                        $idNotificacion = $domicilioNotificacion->id;

                        if ($contactoSolicitante->editable) {
                            $contactoSolicitante->id_domicilio = $domicilioNotificacion->id;
                            $contactoSolicitante->save();
                        } else {
                            Contacto::where('id_persona', $personaSolicitante->id)
                                ->update(['activo' => 0]);  //Se ponen inactivas todos los contactos de la persona propietaria

                            $contactoSolicitante = Contacto::create([
                                'id_persona' => $personaSolicitante->id,
                                'telefono' => trim($request->telefonoSolicitante),
                                'email' => $request->emailSolicitante !== null ? trim(mb_strtolower($request->emailSolicitante)) : null,
                                'id_domicilio' => $idNotificacion,
                                'activo' => true
                            ]);
                        }
                    }
                }
            } else  //Si no es activo
            {
                Contacto::where('id_persona', $personaSolicitante->id)
                    ->update(['activo' => 0]);  //Se ponen inactivas todos los contactos de la persona solicitante

                $contactoSolicitante->activo = 1;  //Si no está activa hay que activarla
                $contactoSolicitante->save();
            }
        } else  //Si no existe es que se ha cambiado el TELÉFONO o el EMAIL
        {
            Contacto::where('id_persona', $personaSolicitante->id)
                ->update(['activo' => 0]);  //Se ponen inactivas todos los contactos de la persona solicitante

            $idNotificacion = null;

            if (strlen($request->domicilioNotificacionSolicitante) > 0) {
                $domicilioNotificacion = DomicilioNotificacion::create([
                    'direccion' => trim(mb_strtoupper($request->domicilioNotificacionSolicitante)),
                ]);
                $idNotificacion = $domicilioNotificacion->id;
            }

            //Se crea un nuevo Solicitante, el cual será el ACTIVO  
            $contactoSolicitante = Contacto::create([
                'id_persona' => $personaSolicitante->id,
                'telefono' => trim($request->telefonoSolicitante),
                'email' => $request->emailSolicitante !== null ? trim(mb_strtolower($request->emailSolicitante)) : null,
                'id_domicilio' => $idNotificacion,
            ]);
        }
        return $contactoSolicitante;
    }

    public function regresaPropiedad(Request $request, $contactoPropietario)
    {
        if ($request->idPropiedadSolicitud) {
            $propiedad = Propiedad::findOrFail($request->idPropiedadSolicitud);

            if ($propiedad) {
                if ($propiedad->editable) {
                    if ($propiedad->id_tipo != $request->tipoPropiedad) {
                        $propiedad->id_tipo = $request->tipoPropiedad;
                    }
                    if ($propiedad->calle != trim(mb_strtoupper($request->callePropiedad))) {
                        $propiedad->calle = $request->callePropiedad !== null ? trim(mb_strtoupper($request->callePropiedad)) : null;
                    }
                    if ($propiedad->numero != trim(mb_strtoupper($request->numeroPropiedad))) {
                        $propiedad->numero = $request->numeroPropiedad !== null ? trim(mb_strtoupper($request->numeroPropiedad)) : null;
                    }
                    if ($propiedad->id_colonia != trim(mb_strtoupper($request->idColoniaPropiedad))) {
                        $propiedad->id_colonia = $request->idColoniaPropiedad !== null ? trim(mb_strtoupper($request->idColoniaPropiedad)) : null;
                    }
                    if ($propiedad->id_localidad != trim(mb_strtoupper($request->idLocalidadPropiedad))) {
                        $propiedad->id_localidad = $request->idLocalidadPropiedad !== null ? trim(mb_strtoupper($request->idLocalidadPropiedad)) : null;
                    }
                    if ($propiedad->superficie != trim(mb_strtoupper($request->superficiePropiedad))) {
                        $propiedad->superficie = $request->superficiePropiedad !== null ? trim(mb_strtoupper($request->superficiePropiedad)) : null;
                    }
                    if ($request->tipoPropiedad === '2' && $propiedad->superficie_construccion != trim(mb_strtoupper($request->superficieConstruccionPropiedad))) {
                        if ($propiedad->superficie_construccion != trim(mb_strtoupper($request->superficieConstruccionPropiedad))) {
                            $propiedad->superficie_construccion = $request->superficieConstruccionPropiedad !== null ? trim(mb_strtoupper($request->superficieConstruccionPropiedad)) : null;
                        }
                    }
                    if ($propiedad->id_contacto != $contactoPropietario->id) {
                        $propiedad->id_contacto = $contactoPropietario->id;
                    }
                    $propiedad->save();
                } else {
                    $propiedad = $this->regresaPropiedadActiva($request, $contactoPropietario);
                }
            }
        } else {
            $propiedad = $this->regresaPropiedadActiva($request, $contactoPropietario);
        }

        return $propiedad;
    }

    public function regresaPropiedadActiva(Request $request, $contactoPropietario)
    {
        $propiedad = Propiedad::where('clave_catastral', trim($request->claveCatastral))
            ->where('id_tipo', $request->tipoPropiedad)
            ->where('superficie', $request->superficiePropiedad)
            ->where('superficie_construccion', $request->superficieConstruccionPropiedad)
            ->where('calle', trim(mb_strtoupper($request->callePropiedad)))
            ->where('numero', trim(mb_strtoupper($request->numeroPropiedad)))
            ->where('id_colonia', $request->idColoniaPropiedad)
            ->where('id_localidad', $request->idLocalidadPropiedad)
            ->where('id_contacto', $contactoPropietario->id);

        if ($request->imgCroquisPropiedad !== null) {
            $propiedad->where('img_croquis', $request->imgCroquisPropiedad);
        }

        $propiedad = $propiedad->first();

        if ($propiedad)  //Si existe entonces la pone como no activa
        {
            if (!$propiedad->activa)  //Si no está activa es porque alguna vez estuvo activa
            {
                Propiedad::where('clave_catastral', trim($request->claveCatastral))
                    ->update(['activa' => 0]);  //Se ponen inactivas todas las propiedades con la clave catastral

                $propiedad->activa = 1;  //Si no está activa hay que activarla
                $propiedad->save();
            }
        } else  //Si NO EXISTE es porque cambió algún dato
        {
            Propiedad::where('clave_catastral', trim($request->claveCatastral))
                ->update(['activa' => 0]);  //Se ponen inactivas todas las propiedades con la clave catastral

            $propiedad = Propiedad::create([
                'clave_catastral' => trim($request->claveCatastral),
                'id_tipo' => $request->tipoPropiedad,
                'calle' => trim(mb_strtoupper($request->callePropiedad)),
                'numero' => trim(mb_strtoupper($request->numeroPropiedad)),
                'id_colonia' => $request->idColoniaPropiedad,
                'id_localidad' => $request->idLocalidadPropiedad,
                'superficie' => $request->superficiePropiedad,
                'superficie_construccion' => $request->superficieConstruccionPropiedad,
                'img_croquis' => $request->imgCroquisPropiedad,
                'id_contacto' => $contactoPropietario->id
            ]);
        }
        return $propiedad;
    }

    public function generaToken()
    {
        // Letras aleatorias (mayúsculas y minúsculas)
        $letras = Str::random(3);

        // Números aleatorios de 3 dígitos
        $numeros = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);

        return $letras . $numeros;
    }

    public function verificaSolicitudDuplicada(Request $request)
    {
        $idPropiedad = $request->idPropiedadSolicitud;
        // $idContactoSolicitud = $request->idContactoSolicitud;
        $tramitesIds = $request->tramitesSeleccionados ?? []; // array de IDs
        $idContactoPropiedad = $request->idContactoPropiedad;

        $fechaLimite = Carbon::now()->subMonths(3);

        $solicitudDuplicada = Solicitud::where('id_propiedad', $idPropiedad)
            ->where('id_destino_obra', $request->idDestinoObra)
            // Nuevo filtro: la fecha_ingreso debe ser posterior a la fecha límite
            ->where('fecha_ingreso', '>', $fechaLimite)
            ->whereHas('propiedad', function ($query) use ($idContactoPropiedad) {
                $query->where('id_contacto', $idContactoPropiedad);
            })
            ->whereHas('tramites', function ($query) use ($tramitesIds) {
                $query->whereIn('id_tramite', $tramitesIds);
            })
            ->exists();

        return $solicitudDuplicada;
    }

    public function store(Request $request)  //Guarda por primera vez la solicitud
    {
        $errores = $this->validaSolicitud($request);

        if ($errores) {
            return back()->withErrors($errores)->with('activeTab', $this->activeTab);
        }

        $solicitudDuplicada = $this->verificaSolicitudDuplicada($request);

        if ($solicitudDuplicada && !$request->boolean('forzar')) {
            throw ValidationException::withMessages([
                'duplicado' => 'Ya existe una SOLICITUD con las mismas características. ¿Deseas guardar de todos modos?'
            ]);
        }

        $tramitesSeleccionados = $request->tramitesSeleccionados;

        $validaPropiedad = true;
        if (in_array($this->ID_CONSTANCIA_UBICACION, $request->tramitesSeleccionados)) {
            $validaPropiedad = false;
        }

        try {
            DB::beginTransaction(); // Inicia la transacción

            if ($validaPropiedad) {
                list($personaPropietario, $esPersonaNueva) = $this->regresaPersonaPropietario($request);
                $contactoPropietario = $this->regresaContactoPropietario($request, $personaPropietario, $esPersonaNueva);

                $propiedad = $this->regresaPropiedad($request, $contactoPropietario);
            }

            $esSolicitante = filter_var($request->esSolicitante, FILTER_VALIDATE_BOOLEAN);
            $solicitaOrganizacion = filter_var($request->solicitaOrganizacion, FILTER_VALIDATE_BOOLEAN);

            if ($esSolicitante && $validaPropiedad) //Si el solicitante es el propietario
            {
                list($personaSolicitante, $esPersonaNueva) = $this->regresaPersonaSolicitante($request, $personaPropietario);
                $contactoSolicitante = $this->regresaContactoSolicitante($request, $personaSolicitante, $contactoPropietario, $esPersonaNueva);
            } else {
                list($personaSolicitante, $esPersonaNueva) = $this->regresaPersonaSolicitante($request);
                $contactoSolicitante = $this->regresaContactoSolicitante($request, $personaSolicitante, null, $esPersonaNueva);
            }

            $solicitud = Solicitud::create([
                'id_contacto' => $contactoSolicitante->id,
                // 'id_propiedad' => $propiedad->id,
                'id_destino_obra' => $request->idDestinoObra,
                'id_estatus' => $request->idEstatusSolicitud,
                'fecha_ingreso' => $request->fecha_ingreso,
                'token_acceso' => $this->generaToken()
            ]);

            if (!$esSolicitante) {
                if ($solicitaOrganizacion && $request->razonSocialSolicitante && strlen(trim($request->razonSocialSolicitante)) > 2) {
                    $this->procesarRazonSocial($solicitud->id, $request->solicitaOrganizacion, $request->razonSocialSolicitante);
                }
            }

            $folioDigital = Str::random(25);

            // Después de crear la solicitud, asigna el ID al folio y guarda los cambios
            $solicitud->folio_digital = $folioDigital;
            $guardaReferencia = false;

            if (!empty($tramitesSeleccionados)) {
                foreach ($tramitesSeleccionados as $tramiteId) {
                    SolicitudTramite::create([
                        'id_solicitud' => $solicitud->id,
                        'id_tramite' => $tramiteId,
                    ]);

                    if ($tramiteId == $this->ID_CONSTANCIA_UBICACION) {
                        $guardaReferencia = true;
                    }
                }
            }

            if (!$guardaReferencia) {
                $solicitud->id_propiedad = $propiedad->id;
            }

            $idSolicitud = $solicitud->id;
            $solicitud->save();


            if ($guardaReferencia && trim($request->referencia) !== '') {
                SolicitudReferencia::create([
                    'id_solicitud' => $solicitud->id,
                    'contenido' => trim(mb_strtoupper($request->referencia)),
                    'id_localidad' => (int) $request->idLocalidadReferencia,
                    'id_tipo_propiedad' => $request->tipoPropiedadReferencia,
                ]);
            }

            $solicitud->load(['contacto', 'propiedad', 'propiedad.contacto']);

            $sortColumn = $request->input('sortColumn', 'fecha_ingreso'); // Columna de ordenación
            $sortDirection = $request->input('sortDirection', 'asc'); // Dirección de ordenación

            $numQuery = $request->input('numQuery', null); // O '' si prefieres cadena vacía
            $folioQuery = $request->input('folioQuery', null); // O '' si prefieres cadena vacía
            $nombreQuery = $request->input('nombreQuery', null); // O '' si prefieres cadena vacía
            $claveCatastralQuery = $request->input('claveCatastralQuery', null); // O '' si prefieres cadena vacía
            $tiposTramitesQuery = $request->input('tiposTramitesQuery', []); // Array vacío para selecciones múltiples
            $tramitesQuery = $request->input('tramitesQuery', []); // Array vacío para selecciones múltiples
            $estatusQuery = $request->input('estatusQuery', null); // O [] si esperas un array de estatus
            $fechaIngresoInicioQuery =  $request->input('fechaIngresoInicioQuery');
            $fechaIngresoFinQuery =  $request->input('fechaIngresoFinQuery');
            $rangoFechasIngresoManual = $request->input('rangoFechasIngresoManual'); // Valor booleano
            $fechaAceptacionInicioQuery =  $request->input('fechaAceptacionInicioQuery');
            $fechaAceptacionFinQuery =  $request->input('fechaAceptacionFinQuery');
            $rangoFechasAceptacionManual = $request->input('rangoFechasAceptacionManual'); // Valor booleano
            $page = $request->input('page', 1);

            $filtros = [
                'fechaIngresoInicioQuery' => $fechaIngresoInicioQuery,
                'fechaIngresoFinQuery' => $fechaIngresoFinQuery,
                'fechaAceptacionInicioQuery' => $fechaAceptacionInicioQuery,
                'fechaAceptacionFinQuery' => $fechaAceptacionFinQuery,
                'numQuery' => $numQuery,
                'folioQuery' => $folioQuery,
                'nombreQuery' => $nombreQuery,
                'claveCatastralQuery' => $claveCatastralQuery,
                'tiposTramitesQuery' => $tiposTramitesQuery,
                'tramitesQuery' => $tramitesQuery,
                'estatusQuery' => $estatusQuery,
                'sortColumn' => $sortColumn,
                'sortDirection' => $sortDirection,
                'rangoFechasIngresoManual' => $rangoFechasIngresoManual,
                'rangoFechasAceptacionManual' => $rangoFechasAceptacionManual,
                'page' => $page,
            ];

            DB::commit(); // Confirma la transacción si todo salió bien

            return redirect()->route('solicitudes', $filtros)
                ->with('success', 'Solicitud N° ' . str_pad($solicitud->id, 4, '0', STR_PAD_LEFT) . ' creada exitosamente')
                ->with('idSolicitud', $idSolicitud)
                ->with('solicitud', $solicitud);

            // return back()->with('success', 'Solicitud N° ' . str_pad($solicitud->id, 4, '0', STR_PAD_LEFT) . ' creada exitosamente')
            //  ->with('solicitudes', Solicitud::all())
            //  ->with('idSolicitud', $idSolicitud)
            //  ->with('solicitud', $solicitud);
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte la transacción si ocurre un error
            return response()->json(['error' => 'Hubo un error al crear la solicitud: ' . $e->getMessage()], 500);
        }
    }

    private function prepararVistaSolicitudes(array $filtros): array
    {
        $solicitudesQuery = $this->obtenerSolicitudes(
            $filtros['fechaIngresoInicioQuery'],
            $filtros['fechaIngresoFinQuery'],
            $filtros['fechaAceptacionInicioQuery'],
            $filtros['fechaAceptacionFinQuery'],
            $filtros['numQuery'],
            $filtros['folioQuery'],
            $filtros['nombreQuery'],
            $filtros['claveCatastralQuery'],
            $filtros['tiposTramitesQuery'],
            $filtros['tramitesQuery'],
            $filtros['localidadesQueryFiltradas'],
            $filtros['estatusQuery'],
            $filtros['sortColumn'] ?? 'fecha_ingreso',
            $filtros['sortDirection'] ?? 'asc'
        );

        $solicitudesFiltradasIds = (clone $solicitudesQuery['query'])->pluck('solicitudes.id');
        $solicitudesFiltradasIdsPrev = $solicitudesQuery['ids_previos'];

        $solicitudes = $solicitudesQuery['query']->paginate(8);

        $tiposPropiedades = TipoPropiedad::orderBy('nombre')->get();
        $destinosObras = DestinoObra::orderBy('nombre')->get();
        $sectores = SectorTramite::orderBy('nombre')->get();
        //$tiposTramites = TipoTramite::with('tramites')->where('activo', true)->get();
        $tiposTramites = TipoTramite::with([
            'tramites' => function ($query) {
                $query
                    ->withCount([
                        'solicitudesTramites as solicitudes_actuales' => function ($query) {
                            $query->whereYear('created_at', now()->year);
                        }
                    ])
                    ->orderByDesc('solicitudes_actuales')
                    ->orderBy('nombre');
            }
        ])->get();

        $localidades = Localidad::orderBy('nombre')->get();

        $resumen = $this->obtenerResumenSolicitudes([
            'filtroChkSolicitudes' => $filtros['filtroChkSolicitudes'],
            'fechaIngresoInicioQuery' => $filtros['fechaIngresoInicioQuery'],
            'fechaIngresoFinQuery' => $filtros['fechaIngresoFinQuery'],
            'fechaAceptacionInicioQuery' => $filtros['fechaAceptacionInicioQuery'],
            'fechaAceptacionFinQuery' => $filtros['fechaAceptacionFinQuery'],
            'numQuery' => $filtros['numQuery'],
            'folioQuery' => $filtros['folioQuery'],
            'nombreQuery' => $filtros['nombreQuery'],
            'claveCatastralQuery' => $filtros['claveCatastralQuery'],
            'tiposTramitesQuery' => $filtros['tiposTramitesQuery'],
            'tramitesQuery' => $filtros['tramitesQuery'],
            'localidadesQueryFiltradas' => $filtros['localidadesQueryFiltradas'],
            'estatusQuery' => $filtros['estatusQuery'],
            'solicitudesFiltradasIds' => $solicitudesFiltradasIds,
            'solicitudesFiltradasIdsPrev' => $solicitudesFiltradasIdsPrev,
        ]);

        return [
            'userAuth' => Auth::user(),
            'solicitudes' => $solicitudes,
            'estatusSolicitud' => $resumen['estatusSolicitud'],
            'tiposPropiedades' => $tiposPropiedades,
            'tiposTramites' => $tiposTramites,
            'destinosObras' => $destinosObras,
            'sectores' => $sectores,
            'localidades' => $localidades,
            'tramites' => $resumen['tramites'],
            'localidadesQuery' => $resumen['localidadesQuery'],
            'numQuery' => $filtros['numQuery'],
            'folioQuery' => $filtros['folioQuery'],
            'nombreQuery' => $filtros['nombreQuery'],
            'fechaIngresoInicioQuery' => $filtros['fechaIngresoInicioQuery'],
            'fechaIngresoFinQuery' => $filtros['fechaIngresoFinQuery'],
            'fechaAceptacionInicioQuery' => $filtros['fechaAceptacionInicioQuery'],
            'fechaAceptacionFinQuery' => $filtros['fechaAceptacionFinQuery'],
            'claveCatastralQuery' => $filtros['claveCatastralQuery'],
            'tiposTramitesQuery' => $filtros['tiposTramitesQuery'],
            'tramitesQuery' => $filtros['tramitesQuery'],
            'localidadesQueryFiltradas' => $filtros['localidadesQueryFiltradas'],
            'estatusQuery' => $filtros['estatusQuery'],
            'filtroChkSolicitudes' => $filtros['filtroChkSolicitudes'],
            'rangoFechasIngresoManual' => $filtros['rangoFechasIngresoManual'],
            'rangoFechasAceptacionManual' => $filtros['rangoFechasAceptacionManual'],
            'sortColumn' => $filtros['sortColumn'] ?? 'fecha_ingreso',
            'sortDirection' => $filtros['sortDirection'] ?? 'asc',
            'paraNuevaSolicitud' => $filtros['paraNuevaSolicitud'] ?? false,
            'paginaActual' => $filtros['page'],
            'periodoActual' => $filtros['periodoActual']
        ];
    }

    public function procesarRazonSocial(?int $idSolicitud, bool $solicitaOrganizacion, ?string $razonSocialSolicitante): ?SolicitudRazonSocial
    {
        if (is_null($idSolicitud)) {
            $razonSocial = null;
        } else {
            // Busca la razón social existente para la solicitud
            $razonSocial = SolicitudRazonSocial::where('id_solicitud', $idSolicitud)->first();
        }

        if ($solicitaOrganizacion && $razonSocialSolicitante && strlen(trim($razonSocialSolicitante)) > 2) {
            // Si no existe, la crea
            if (!$razonSocial) {
                return SolicitudRazonSocial::create([
                    'id_solicitud' => $idSolicitud,
                    'nombre' => trim(mb_strtoupper($razonSocialSolicitante)),
                ]);
            }

            // Si ya existe, la actualiza solo si el nombre ha cambiado
            if ($razonSocial->nombre !== trim(mb_strtoupper($razonSocialSolicitante))) {
                $razonSocial->nombre = trim(mb_strtoupper($razonSocialSolicitante));
                $razonSocial->save();
            }

            return $razonSocial;
        } else {
            // Si la organización no se solicita, busca si existe y la elimina
            if ($razonSocial) {
                $razonSocial->delete();
            }

            return null;
        }
    }

    public function actualizaEditablesRequisitosDocumentacion($idsRequisitosString)
    {
        $idsRequisitosArray = explode(',', $idsRequisitosString);
        $idsRequisitos = array_map('intval', $idsRequisitosArray);

        RequisitoDocumentacion::whereIn('id', $idsRequisitos)
            ->update(['editable' => 0]);
    }

    public function actualizaEditablesCatalogoTramitesRequisitos($tramitesSeleccionados, $idsRequisitosString)
    {
        $idsRequisitosArray = explode(',', $idsRequisitosString);
        $idsRequisitos = array_map('intval', $idsRequisitosArray);

        DB::table('catalogo_tramites_requisitos')
            // Condición 1: El registro en la pivote debe pertenecer a uno de los trámites seleccionados.
            ->whereIn('id_tramite_catalogo', $tramitesSeleccionados)
            // Condición 2: El registro en la pivote debe pertenecer a uno de los requisitos entregados.
            ->whereIn('id_requisito', $idsRequisitos)
            // Paso 3: Realizar la actualización.
            ->update([
                'editable' => 0,
            ]);
    }

    public function update(Request $request, $id)
    {
        $errores = $this->validaSolicitud($request, $id);

        if ($errores) {
            return back()->withErrors($errores)->with('activeTab', $this->activeTab);
        }

        $propiedad = null;
        $personaPropietario = null;
        $contactoPropietario = null;

        $validaPropiedad = true;
        if (in_array($this->ID_CONSTANCIA_UBICACION, $request->get('tramitesSeleccionados', []))) {
            $validaPropiedad = false;
        }

        DB::beginTransaction();
        try {
            if ($validaPropiedad) {
                list($personaPropietario, $esPersonaNueva) = $this->regresaPersonaPropietario($request);
                $contactoPropietario = $this->regresaContactoPropietario($request, $personaPropietario, $esPersonaNueva);
                $propiedad = $this->regresaPropiedad($request, $contactoPropietario);
            }

            $esSolicitante = filter_var($request->esSolicitante, FILTER_VALIDATE_BOOLEAN);
            $solicitaOrganizacion = filter_var($request->solicitaOrganizacion, FILTER_VALIDATE_BOOLEAN);

            $solicitud = Solicitud::find($id);

            if ($esSolicitante && $validaPropiedad) //Si el solicitante es el propietario
            {
                list($personaSolicitante, $esPersonaNueva) = $this->regresaPersonaSolicitante($request, $personaPropietario);
                $contactoSolicitante = $this->regresaContactoSolicitante($request, $personaSolicitante, $contactoPropietario, $esPersonaNueva);
            } else {
                list($personaSolicitante, $esPersonaNueva) = $this->regresaPersonaSolicitante($request);
                $contactoSolicitante = $this->regresaContactoSolicitante($request, $personaSolicitante, null, $esPersonaNueva);
                if ($solicitaOrganizacion && $request->razonSocialSolicitante && strlen(trim($request->razonSocialSolicitante)) > 2) {
                    $this->procesarRazonSocial($id, $request->solicitaOrganizacion, $request->razonSocialSolicitante);
                }
            }
            $guardaReferencia = false;
            SolicitudTramite::where('id_solicitud', $id)->delete();
            if (!empty($request->tramitesSeleccionados)) {
                foreach ($request->tramitesSeleccionados as $tramiteId) {
                    SolicitudTramite::create([
                        'id_solicitud' => $id,
                        'id_tramite' => $tramiteId,
                    ]);

                    if ($tramiteId == $this->ID_CONSTANCIA_UBICACION) {
                        $guardaReferencia = true;
                    }
                }
            }
            if ($guardaReferencia) {
                $solicitud->id_propiedad = null;
            } else {
                $solicitud->id_propiedad = $propiedad ? $propiedad->id : null;
            }
            $solicitud->id_contacto = $contactoSolicitante->id;
            $solicitud->id_destino_obra = $request->idDestinoObra;
            $solicitud->id_estatus = $request->idEstatusSolicitud;
            $solicitud->fecha_ingreso = $request->fecha_ingreso;

            if (
                $guardaReferencia &&
                (trim($request->referencia) !== ''
                    || $request->idLocalidadReferencia !== null
                    || $request->tipoPropiedadReferencia !== null)
            ) {
                $referencia = SolicitudReferencia::where('id_solicitud', $id)->first();
                if ($referencia) {
                    $referencia->contenido = trim(mb_strtoupper($request->referencia));
                    $referencia->id_localidad = $request->idLocalidadReferencia;
                    $referencia->id_tipo_propiedad = $request->tipoPropiedadReferencia;
                    $referencia->save();
                } else {
                    SolicitudReferencia::create([
                        'id_solicitud' => $solicitud->id,
                        'contenido' => trim(mb_strtoupper($request->referencia)),
                        'id_localidad' =>  $request->idLocalidadReferencia,
                        'id_tipo_propiedad' => $request->tipoPropiedadReferencia,
                    ]);
                }
            }
            $archivo = null;
            if ($request->idEstatusSolicitud == 99)   //Cuando es un TRÁMITE CONCLUÍDO
            {
                $personaSolicitante->editable = 0;
                $personaSolicitante->save();
                $contactoSolicitante->editable = 0;
                $contactoSolicitante->save();

                if ($validaPropiedad) {
                    if ($personaPropietario) {
                        $personaPropietario->editable = 0;
                        $personaPropietario->save();
                    }
                    if ($contactoPropietario) {
                        $contactoPropietario->editable = 0;
                        $contactoPropietario->save();
                    }
                    if ($propiedad) {
                        $propiedad->editable = 0;
                        $propiedad->fecha_aceptacion = now();
                    }
                    $solicitudAceptada = SolicitudAceptada::create([
                        'activa'       => true,
                    ]);
                    $solicitud->folio = $solicitudAceptada->id;
                    $solicitud->fecha_aceptacion = now();
                    if ($solicitud->croquis_aux) {
                        $archivo =  $solicitud->croquis_aux->img;
                        $solicitud->croquis_aux->delete();
                    }
                    if ($solicitud->referencia) {
                        $solicitud->referencia->delete();
                    }
                } else {
                    if ($solicitud->propiedad)  //Si ya tenía una propiedad asignada
                    {
                        $archivo =  $solicitud->propiedad->img_croquis;
                        $solicitud->propiedad->delete();
                    }
                    if ($personaPropietario) {
                        $personaPropietario->delete();
                    }
                    if ($contactoPropietario) {
                        $contactoPropietario->delete();
                    }
                }
                if ($archivo) {
                    // Verificar si el archivo existe
                    if (Storage::disk('public')->exists('croquis/' . $archivo)) {
                        Storage::disk('public')->delete('croquis/' . $archivo);
                    }
                }
                $this->insertaTramite($request, $solicitud->id);
            }

            if ($validaPropiedad && $propiedad) $propiedad->save();

            $solicitud->save();

            $this->actualizaDocumentacionSolicitud($id, $request->requisitosEntregados ?? "");

            if ($request->idEstatusSolicitud == 99) {
                $this->actualizaEditablesRequisitosDocumentacion($request->requisitosEntregados);
                $this->actualizaEditablesCatalogoTramitesRequisitos($request->tramitesSeleccionados, $request->requisitosEntregados);
                $this->enviarSolicitudPorEmail($solicitud->id);
            }

            DB::commit();

            $sortColumn = $request->input('sortColumn', 'fecha_ingreso'); // Columna de ordenación
            $sortDirection = $request->input('sortDirection', 'asc'); // Dirección de ordenación

            $numQuery = $request->input('numQuery', null); // O '' si prefieres cadena vacía
            $folioQuery = $request->input('folioQuery', null); // O '' si prefieres cadena vacía
            $nombreQuery = $request->input('nombreQuery', null); // O '' si prefieres cadena vacía
            $claveCatastralQuery = $request->input('claveCatastralQuery', null); // O '' si prefieres cadena vacía
            $tiposTramitesQuery = $request->input('tiposTramitesQuery', []); // Array vacío para selecciones múltiples
            $tramitesQuery = $request->input('tramitesQuery', []); // Array vacío para selecciones múltiples
            $localidadesQueryFiltradas = $request->input('localidadesQueryFiltradas');
            $estatusQuery = $request->input('estatusQuery', null); // O [] si esperas un array de estatus
            $fechaIngresoInicioQuery =  $request->input('fechaIngresoInicioQuery');
            $fechaIngresoFinQuery =  $request->input('fechaIngresoFinQuery');
            $rangoFechasIngresoManual = $request->input('rangoFechasIngresoManual'); // Valor booleano
            $fechaAceptacionInicioQuery =  $request->input('fechaIngresAceptacionQuery');
            $fechaAceptacionFinQuery =  $request->input('fechaAceptacionFinQuery');
            $rangoFechasAceptacionManual = $request->input('rangoFechasAceptacionManual'); // Valor booleano
            $page = $request->input('page', 1);

            // Obtener el ID del periodo actual
            $periodoId = SettingsHelper::get('periodo_actual');

            // Usar el ID para buscar el objeto completo
            $periodoActual = Periodo::find($periodoId);

            if (empty($tramitesQuery) && empty($estatusQuery)) {
                $filtroChkSolicitudes = 0;
            }

            if ($tramitesQuery && empty($estatusQuery)) {
                $filtroChkSolicitudes = 1;
            }

            if (empty($tramitesQuery) && $estatusQuery) {
                $filtroChkSolicitudes = 2;
            }

            $filtros = [
                'fechaIngresoInicioQuery' => $fechaIngresoInicioQuery,
                'fechaIngresoFinQuery' => $fechaIngresoFinQuery,
                'fechaAceptacionInicioQuery' => $fechaAceptacionInicioQuery,
                'fechaAceptacionFinQuery' => $fechaAceptacionFinQuery,
                'numQuery' => $numQuery,
                'folioQuery' => $folioQuery,
                'nombreQuery' => $nombreQuery,
                'claveCatastralQuery' => $claveCatastralQuery,
                'tiposTramitesQuery' => $tiposTramitesQuery,
                'localidadesQueryFiltradas' => $localidadesQueryFiltradas,
                'tramitesQuery' => $tramitesQuery,
                'estatusQuery' => $estatusQuery,
                'filtroChkSolicitudes' => $filtroChkSolicitudes,
                'sortColumn' => $sortColumn,
                'sortDirection' => $sortDirection,
                'periodoActual' => $periodoActual,
                'rangoFechasIngresoManual' => $rangoFechasIngresoManual,
                'rangoFechasAceptacionManual' => $rangoFechasAceptacionManual,
                'paraNuevaSolicitud' => false,
                'page' => $page,
            ];

            return redirect()->route('solicitudes', $filtros)->with('success', 'Solicitud actualizada con éxito!');
        } catch (Throwable $e) { // Usa Throwable para capturar cualquier tipo de error
            DB::rollBack();

            dd($e);
        }
    }

    public function insertaTramite(Request $request, $idSolicitud)
    {
        // Calculamos el día hábil siguiente
        $hoy = now();
        $manana = $hoy->addDay();

        // Si es sábado (6), saltamos al lunes (+2 días)
        if ($manana->isSaturday()) {
            $manana->addDays(2);
        }
        // Si es domingo (0), saltamos al lunes (+1 día)
        elseif ($manana->isSunday()) {
            $manana->addDay();
        }

        // Inyectamos los valores al request antes de validar
        $request->merge([
            'idSolicitud' => $idSolicitud,
            'idEstatus'   => 1,
            'fecha_inicio' => $manana->format('Y-m-d') // Formato estándar de BD
        ]);

        // 1. Validación estricta de los datos recibidos
        $validated = $request->validate([
            'idSolicitud' => 'required|exists:solicitudes,id',
            'tramitesSeleccionados' => 'required|array|min:1',
            'tramitesSeleccionados.*' => 'exists:catalogo_tramites,id',
            'idContactoPropiedad'  => 'required|exists:contactos,id',
            'idPropiedadSolicitud' => 'required|exists:propiedades,id',
            'idEstatus'   => 'required|exists:estatus_tramites,id',
            'fecha_inicio' => 'required|date',
        ]);


        try {
            $tramitesCreados = [];

            // Ciclo para crear cada trámite seleccionado
            foreach ($validated['tramitesSeleccionados'] as $idCatalogo) {
                $tramite = Tramite::create([
                    'id_solicitud' => $validated['idSolicitud'],
                    'id_tramite'   => $idCatalogo, // Este es el valor que varía en el ciclo
                    'id_contacto'  => $validated['idContactoPropiedad'],
                    'id_propiedad' => $validated['idPropiedadSolicitud'],
                    'id_estatus'   => $validated['idEstatus'],
                    'fecha_inicio' => $validated['fecha_inicio'],
                    'fecha_fin'    => null,
                ]);

                $tramitesCreados[] = $tramite;
            }

            return true;
        } catch (\Exception $e) {
            // Si algo falla, el DB::transaction hará rollback de TODOS los trámites creados en el ciclo
            DB::rollBack();
            Log::error("Fallo en actualización: " . $e->getMessage());

            dd($e->getMessage());
        }
    }

    public function actualizaDocumentacionSolicitud(int $idSolicitud, $requisitosEntregados)
    {
        $requisitosArray = explode(',', $requisitosEntregados);

        // b) array_filter: Elimina elementos vacíos que puedan surgir si la cadena está vacía o mal formada.
        $requisitosArray = array_filter($requisitosArray);

        // c) array_map: Convierte cada elemento del array (que son strings) a enteros.
        $requisitosEntregados = array_map('intval', $requisitosArray);

        try {
            // 1. Encontrar la solicitud por su ID.
            $solicitud = Solicitud::findOrFail($idSolicitud);

            // 2. Usar el método sync() de la relación.
            // sync() compara el array de IDs ($requisitosEntregados) con los registros existentes:
            // - Si un ID ya existe, lo deja.
            // - Si un ID NO existe, lo adjunta (inserta).
            // - Si un ID SÍ existe pero no está en el array, lo separa (elimina).
            $solicitud->requisitos_docs()->sync($requisitosEntregados);

            return true; // Éxito en la operación.

        } catch (QueryException $e) {
            // Manejo de errores de MySQL (p. ej., clave foránea no existente, error de conexión).
            Log::error("Error de DB al actualizar documentación de solicitud ID {$idSolicitud}: " . $e->getMessage());
            return false;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Manejo de error si la Solicitud no existe.
            Log::warning("Solicitud ID {$idSolicitud} no encontrada para la actualización.");
            return false;
        } catch (\Exception $e) {
            // Manejo de cualquier otro error.
            Log::error("Error inesperado: " . $e->getMessage());
            return false;
        }
    }

    public function enviarSolicitudPorEmail($idSolicitud)
    {
        $solicitud = Solicitud::with(['contacto.persona', 'propiedad.contacto.persona', 'tramites'])
            ->find($idSolicitud);


        // Verifica si hay email antes de intentar enviar
        if ($solicitud && !empty($solicitud->contacto->email)) {
            Mail::to($solicitud->contacto->email)
                ->queue(new SolicitudMail($solicitud));

            return response()->json(['message' => 'Correo enviado correctamente!!!!!']);
        }

        return response()->json(['message' => 'No se envió el correo porque no hay email.']);
    }
}
