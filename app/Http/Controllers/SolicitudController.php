<?php

namespace App\Http\Controllers;

use App\Models\CatalogoTramite;
use Illuminate\Http\Request;
use App\Models\Solicitud;
use App\Models\EstatusSolicitud;
use App\Models\Localidad;
use App\Models\Periodo;
use App\Models\Area;
use App\Models\Solicitante;
use App\Models\ConfiguracionUsuario;
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
    public function nueva()
    {
        $userAuth = Auth::user();

        return Inertia::render('Solicitudes/FormularioLink', [
            'nuevaSolicitud' => true,
            'solicitudSeleccionada' => null,
            'userAuth' => $userAuth,
            'estatusList' => EstatusSolicitud::orderBy('id', 'asc')->get(),
            'areasList' => Area::orderBy('nombre', 'asc')->get(),
            'localidadesList' => Localidad::orderBy('nombre', 'asc')->get()
        ]);
    }

    public function editar(Request $request)
    {
        $userAuth = Auth::user();

        $idSolicitud = $request->input('id_solicitud');

        $solicitud = Solicitud::with('solicitante', 'area', 'estatus', 'user', 'solicitante.localidad')->findOrFail($idSolicitud);

        return Inertia::render('Solicitudes/FormularioLink', [
            'nuevaSolicitud' => false,
            'solicitudSeleccionada' => $solicitud,
            'userAuth' => $userAuth,
            'estatusList' => EstatusSolicitud::orderBy('id', 'asc')->get(),
            'areasList' => Area::orderBy('nombre', 'asc')->get(),
            'localidadesList' => Localidad::orderBy('nombre', 'asc')->get()
        ]);
    }

    public function buscarSolicitante(Request $request)
    {
        $nombre = $request->input('nombre');
        $apellidos = $request->input('apellidos');
        $curp = $request->input('curp');
        $telefono = $request->input('numero_telefonico');
        $id_localidad = $request->input('id_localidad');

        $query = Solicitante::with('localidad');

        $noVacios = 0;

        // Solo se filtra si el campo trae texto real
        if (!empty(trim($nombre))) {
            $query->where('nombre', 'LIKE', "%{$nombre}%");
            $noVacios++;
        }

        if (!empty(trim($apellidos))) {
            $query->where('apellidos', 'LIKE', "%{$apellidos}%");
            $noVacios++;
        }

        if (!empty(trim($curp))) {
            $query->where('curp', 'LIKE', "%{$curp}%");
            $noVacios++;
        }

        if (!empty(trim($telefono))) {
            $query->where('numero_telefonico', 'LIKE', "%{$telefono}%");
            $noVacios++;
        }

        // dd($request->all(), $noVacios);

        if (!empty(trim($id_localidad)) && $noVacios >= 3) {
            // dd('entro');
            $query->where('id_localidad', "{$id_localidad}");
        }

        // Si absolutamente todos los campos están vacíos, devolvemos vacío
        if (empty(trim($nombre)) && empty(trim($apellidos)) && empty(trim($curp)) && empty(trim($telefono)) && empty(trim($id_localidad))) {
            return response()->json([]);
        }

        return response()->json($query->limit(10)->get());
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


    public function index(Request $request)
    {

        $localidades = Localidad::orderBy('nombre', 'asc')->get();
        $userAuth = Auth::user();

        $idUsuario = Auth::id();
        $config = ConfiguracionUsuario::where('id_user', $idUsuario)->first();

        $fechaIngresoInicioQuery =  $request->input('fechaIngresoInicioQuery');
        $fechaIngresoFinQuery =  $request->input('fechaIngresoFinQuery');
        $fechaAceptacionInicioQuery = $request->input('fechaAceptacionInicioQuery');
        $fechaAceptacionFinQuery =  $request->input('fechaAceptacionFinQuery');
        $estatusQuery = $request->input('estatusQuery');
        $page = (int) $request->input('page', 1);

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

        // Obtener el ID del periodo actual
        $periodoId = SettingsHelper::get('periodo_actual');

        // Usar el ID para buscar el objeto completo
        $periodoActual = Periodo::find($periodoId);

        $query = Solicitud::with('solicitante', 'area', 'estatus', 'user', 'solicitante.localidad');

        // Filtro por rango de fecha de ingreso
        if ($request->filled('fechaIngresoInicioQuery') && $request->filled('fechaIngresoFinQuery')) {
            $query->whereBetween('fecha', [ // Cambia 'fecha' por el nombre de tu columna en la base de datos
                $request->input('fechaIngresoInicioQuery'),
                $request->input('fechaIngresoFinQuery')
            ]);
        }

        if ($request->filled('searchQuery')) {
            $texto = trim($request->input('searchQuery'));

            $query->where(function ($q) use ($texto) {

                $q->where('id', 'like', "%{$texto}%")
                    ->orWhere('peticion', 'like', "%{$texto}%")

                    ->orWhereHas('solicitante', function ($subQuery) use ($texto) {
                        $subQuery->where('nombre', 'like', "%{$texto}%")
                            ->orWhere('apellidos', 'like', "%{$texto}%");
                    });
            });

            if (ctype_digit($texto)) {

                // ==========================================
                // BÚSQUEDA POR ID
                // ==========================================

                $query->orderByRaw(
                    'CASE
                WHEN id = ? THEN 0
                WHEN id LIKE ? THEN 1
                ELSE 2
            END ASC',
                    [
                        (int) $texto,
                        "{$texto}%",
                    ]
                );

                $query->orderBy('id', 'asc');
            } else {

                // ==========================================
                // BÚSQUEDA POR NOMBRE / APELLIDOS
                // ==========================================

                $query->orderByRaw("
            CASE

                -- 1. El nombre comienza con el texto
                --    JOSE LUIS
                --    JOSE ANTONIO
                WHEN EXISTS (
                    SELECT 1
                    FROM solicitantes AS s1
                    WHERE s1.id = solicitudes.id_solicitante
                      AND s1.nombre LIKE ?
                ) THEN 1

                -- 2. El apellido comienza con el texto
                --    HERNANDEZ PEREZ
                --    HERNANDEZ GARCIA
                WHEN EXISTS (
                    SELECT 1
                    FROM solicitantes AS s2
                    WHERE s2.id = solicitudes.id_solicitante
                      AND s2.apellidos LIKE ?
                ) THEN 2

                -- 3. El texto aparece después en el nombre
                --    MIGUEL JOSE
                --    HECTOR JOSE
                WHEN EXISTS (
                    SELECT 1
                    FROM solicitantes AS s3
                    WHERE s3.id = solicitudes.id_solicitante
                      AND s3.nombre LIKE ?
                ) THEN 3

                -- 4. El texto aparece después en los apellidos
                --    GARCIA HERNANDEZ
                --    PEREZ HERNANDEZ
                WHEN EXISTS (
                    SELECT 1
                    FROM solicitantes AS s4
                    WHERE s4.id = solicitudes.id_solicitante
                      AND s4.apellidos LIKE ?
                ) THEN 4

                ELSE 5
            END ASC
        ", [
                    "{$texto}%",
                    "{$texto}%",
                    "% {$texto}%",
                    "% {$texto}%",
                ]);

                // Orden alfabético general del nombre
                $query->orderByRaw("
            (
                SELECT s5.nombre
                FROM solicitantes AS s5
                WHERE s5.id = solicitudes.id_solicitante
                LIMIT 1
            ) ASC
        ");

                // Después apellidos
                $query->orderByRaw("
            (
                SELECT s6.apellidos
                FROM solicitantes AS s6
                WHERE s6.id = solicitudes.id_solicitante
                LIMIT 1
            ) ASC
        ");
            }
        }



        if (!empty($request->input('areasQuery'))) {
            $query->whereIn('id_area', $request->input('areasQuery'));
        }

        $queryContadores = clone $query;

        $totalesEstatusQuery = [
            'todas' => (clone $queryContadores)->count(),

            'enTramite' => (clone $queryContadores)
                ->where('id_estatus', 1)
                ->count(),

            'aprobadas' => (clone $queryContadores)
                ->where('id_estatus', 2)
                ->count(),

            'apoyoEntregado' => (clone $queryContadores)
                ->where('id_estatus', 3)
                ->count(),

            'canceladas' => (clone $queryContadores)
                ->where('id_estatus', 4)
                ->count(),
        ];

        if ($request->filled('estatusQuery') && $request->input('estatusQuery') !== 'todas') {

            $idStatus = match ($request->input('estatusQuery')) {
                'enTramite'      => 1,
                'aprobadas'      => 2,
                'apoyoEntregado' => 3,
                'canceladas'     => 4,
                default          => null,
            };

            if ($idStatus !== null) {
                $query->where('id_estatus', $idStatus);
            }
        }

        // Paginación
        $solicitudes = $query->paginate(8);

        $totalSolicitudes = Solicitud::count();

        return Inertia::render('Solicitudes/Index', [
            'userAuth' => $userAuth,
            'periodoActual' => $periodoActual,
            'solicitudes' => $solicitudes,
            'totalesEstatusQuery' => $totalesEstatusQuery,
            'localidadesList' => $localidades,
            'areasList' => Area::orderBy('nombre', 'asc')->get(),
            'estatusList' => EstatusSolicitud::orderBy('id', 'asc')->get(),
            'searchQuery' => $request->input('searchQuery'),
            'fechaIngresoInicioQuery' => $fechaIngresoInicioQuery,
            'fechaIngresoFinQuery' => $fechaIngresoFinQuery,
            'estatusQuery' => $estatusQuery,
            'totalSolicitudes' => $totalSolicitudes,
            'page' => $page
        ]);
    }


    public function store(Request $request)
    {
        // dd($request->all());

        // 1. Validación de los campos entrantes
        $validatedData = $request->validate([
            'solicitante_curp' => [
                'nullable',
                'string',
                'size:18',
                'regex:/^([A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d])(\d)$/'
            ],
            'solicitante_nombre' => 'required|string|max:35',
            'solicitante_apellidos' => 'required|string|max:60',
            'solicitante_numero_telefonico' => 'nullable|string|digits:10',
            'solicitante_id_localidad' => 'required|exists:localidades,id',
            'peticion' => 'required|string|max:255',
            'cantidad_aprobada' => 'nullable|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'observaciones' => 'nullable|string|max:255',

        ], [
            // Mensajes personalizados para el CURP
            'solicitante_curp.size' => 'El CURP debe contener exactamente 18 caracteres.',
            'solicitante_curp.regex' => 'La estructura o formato del CURP no es válida.',

            // Mensajes para el nombre
            'solicitante_nombre.required' => 'El nombre del solicitante es obligatorio.',
            'solicitante_nombre.max' => 'El nombre no puede tener más de 35 caracteres.',

            // Mensajes para los apellidos
            'solicitante_apellidos.required' => 'Los apellidos del solicitante son obligatorios.',
            'solicitante_apellidos.max' => 'Los apellidos no pueden tener más de 60 caracteres.',

            // Mensajes para el teléfono
            'solicitante_numero_telefonico.digits' => 'El número telefónico debe contener exactamente 10 dígitos.',

            // Mensajes para la localidad
            'solicitante_id_localidad.required' => 'Debe seleccionar una localidad.',
            'solicitante_id_localidad.exists' => 'La localidad seleccionada no es válida.',

            'peticion.required' => 'Debes escribir una petición.',
            'peticion.max' => 'La petición no puede tener más de 255 caracteres.',

            'cantidad_aprobada.numeric' => 'La cantidad aprobada debe ser un número.',
            'cantidad_aprobada.decimal' => 'La cantidad aprobada puede tener máximo 2 decimales.',
            'cantidad_aprobada.min' => 'La cantidad aprobada debe ser mayor a 0.',
            'cantidad_aprobada.max' => 'La cantidad aprobada no puede exceder $99,999.99.',

            'observaciones.max' => 'Las observaciones no pueden tener más de 255 caracteres.',
        ]);

        // 2. Extracción de los datos del solicitante
        try {
            $solicitud = DB::transaction(function () use ($request) {

                // 1. Preparamos y buscamos/creamos al solicitante
                $datosSolicitante = [
                    'curp' => $request->input('solicitante_curp') ?: null,
                    'nombre' => mb_strtoupper(trim($request->input('solicitante_nombre'))),
                    'apellidos' => mb_strtoupper(trim($request->input('solicitante_apellidos'))),
                    'numero_telefonico' => $request->input('solicitante_numero_telefonico') ?: null,
                    'id_localidad' => $request->input('solicitante_id_localidad'),
                ];

                $solicitante = Solicitante::firstOrCreate($datosSolicitante);

                // 2. Creamos la solicitud
                $nuevaSolicitud = Solicitud::create([
                    'fecha' => $request->input('fecha'),
                    'id_solicitante' => $solicitante->id,
                    'id_area' => $request->input('id_area'),
                    'id_estatus' => $request->input('id_estatus'),
                    'peticion' => $request->input('peticion'),
                    'cantidad_aprobada' => $request->input('cantidad_aprobada'),
                    'observaciones' => $request->input('observaciones'),
                    'id_user' => Auth::id(),
                ]);

                // 3. Cargamos las relaciones que necesites mostrar en tu tabla o frontend
                $nuevaSolicitud->load(['solicitante', 'solicitante.localidad', 'area', 'estatus', 'user']); // Reemplaza por los nombres reales de tus relaciones en el Modelo Solicitud

                return $nuevaSolicitud;
            });

            // Respuesta si todo salió bien
            // return redirect()->back()->with([
            //     'success' => 'Solicitud creada con éxito.',
            //     'solicitud' => $solicitud
            // ]);

            return redirect()->route('solicitudes', [
                'page' => $request->input('paginaActual'),
                'searchQuery' => $request->input('searchQuery'),
                'estatusQuery' => $request->input('estatusQuery'),
                'areasQuery' => $request->input('areasQuery'),
                'fechaIngresoInicioQuery' => $request->input('fechaIngresoInicioQuery'),
                'fechaIngresoFinQuery' => $request->input('fechaIngresoFinQuery'),
            ])->with([
                'success' => 'Solicitud creada con éxito.',
                'solicitud' => $solicitud
            ]);
        } catch (\Exception $e) {
            // Opcional: Registra el error en storage/logs/laravel.log para que puedas depurarlo
            Log::error('Error al crear la solicitud: ' . $e->getMessage());

            // Respuesta si ocurrió un error
            return redirect()->back()
                ->withInput() // Mantiene los datos que el usuario escribió en el formulario
                ->withErrors(['error' => 'Ocurrió un error al guardar la solicitud. Por favor, inténtalo de nuevo.']);
            // O si es API: return response()->json(['message' => 'Error al procesar la solicitud', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        // 1. Validación de los campos entrantes
        $validatedData = $request->validate([
            'solicitante_curp' => [
                'nullable',
                'string',
                'size:18',
                'regex:/^([A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d])(\d)$/'
            ],
            'solicitante_nombre' => 'required|string|max:35',
            'solicitante_apellidos' => 'required|string|max:60',
            'solicitante_numero_telefonico' => 'nullable|string|digits:10',
            'solicitante_id_localidad' => 'required|exists:localidades,id',
            'peticion' => 'required|string|max:255',
            'cantidad_aprobada' => 'nullable|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'observaciones' => 'nullable|string|max:255',

        ], [
            // Mensajes personalizados para el CURP
            'solicitante_curp.size' => 'El CURP debe contener exactamente 18 caracteres.',
            'solicitante_curp.regex' => 'La estructura o formato del CURP no es válida.',

            // Mensajes para el nombre
            'solicitante_nombre.required' => 'El nombre del solicitante es obligatorio.',
            'solicitante_nombre.max' => 'El nombre no puede tener más de 35 caracteres.',

            // Mensajes para los apellidos
            'solicitante_apellidos.required' => 'Los apellidos del solicitante son obligatorios.',
            'solicitante_apellidos.max' => 'Los apellidos no pueden tener más de 60 caracteres.',

            // Mensajes para el teléfono
            'solicitante_numero_telefonico.digits' => 'El número telefónico debe contener exactamente 10 dígitos.',

            // Mensajes para la localidad
            'solicitante_id_localidad.required' => 'Debe seleccionar una localidad.',
            'solicitante_id_localidad.exists' => 'La localidad seleccionada no es válida.',

            'peticion.required' => 'Debes escribir una petición.',
            'peticion.max' => 'La petición no puede tener más de 255 caracteres.',

            'cantidad_aprobada.numeric' => 'La cantidad aprobada debe ser un número.',
            'cantidad_aprobada.decimal' => 'La cantidad aprobada puede tener máximo 2 decimales.',
            'cantidad_aprobada.min' => 'La cantidad aprobada debe ser mayor a 0.',
            'cantidad_aprobada.max' => 'La cantidad aprobada no puede exceder $99,999.99.',

            'observaciones.max' => 'Las observaciones no pueden tener más de 255 caracteres.',
        ]);

        // 2. Extracción de los datos del solicitante
        try {
            $solicitud = DB::transaction(function () use ($request, $id) {

                // 1. Preparamos y buscamos/creamos al solicitante
                $datosSolicitante = [
                    'curp' => $request->input('solicitante_curp') ?: null,
                    'nombre' => mb_strtoupper(trim($request->input('solicitante_nombre'))),
                    'apellidos' => mb_strtoupper(trim($request->input('solicitante_apellidos'))),
                    'numero_telefonico' => $request->input('solicitante_numero_telefonico') ?: null,
                    'id_localidad' => $request->input('solicitante_id_localidad'),
                ];

                $solicitante = Solicitante::firstOrCreate($datosSolicitante);

                $solicitud = Solicitud::findOrFail($id);

                $estatusActual = $solicitud->estatus?->nombre;

                // Verificar si la solicitud ya está concluida
                $solicitudCerrada = in_array(
                    mb_strtoupper(trim($estatusActual ?? '')),
                    ['APOYO ENTREGADO', 'CANCELADA']
                );

                // Si ya está cerrada, no permitir modificación
                if ($solicitudCerrada) {
                    abort(403, 'La solicitud ya está concluida y no puede modificarse.');
                }

                // Nuevo estatus
                $nuevoEstatusId = $request->input('id_estatus');

                $estatusNuevo = EstatusSolicitud::findOrFail($nuevoEstatusId);

                $nombreEstatusNuevo = mb_strtoupper(
                    trim($estatusNuevo->nombre)
                );

                // Determinar fecha de resolución
                $fechaResolucion = null;

                if (in_array($nombreEstatusNuevo, ['APOYO ENTREGADO', 'CANCELADA'])) {

                    $request->validate([
                        'fecha_resolucion' => [
                            'required',
                            'date',
                            'after_or_equal:fecha',
                            'before_or_equal:today',
                        ],
                    ]);

                    $fechaResolucion = $request->input('fecha_resolucion');
                }

                $solicitud->fecha = $request->input('fecha');
                $solicitud->id_solicitante = $solicitante->id;
                $solicitud->id_area = $request->input('id_area');
                $solicitud->id_estatus = $request->input('id_estatus');
                $solicitud->peticion = mb_strtoupper(trim($request->input('peticion')));
                $solicitud->cantidad_aprobada = $request->input('cantidad_aprobada');
                $solicitud->observaciones = $request->input('observaciones') !== null
                    ? mb_strtoupper(trim($request->input('observaciones')))
                    : null;
                $solicitud->fecha_resolucion = $fechaResolucion
                    ? $fechaResolucion . ' ' . now()->format('H:i:s')
                    : null;
                $solicitud->id_user = Auth::id();
                $solicitud->save();

                // 3. Cargamos las relaciones que necesites mostrar en tu tabla o frontend
                $solicitud->load(['solicitante', 'solicitante.localidad', 'area', 'estatus', 'user']); // Reemplaza por los nombres reales de tus relaciones en el Modelo Solicitud

                return $solicitud;
            });

            // $periodoId = SettingsHelper::get('periodo_actual');

            // Respuesta si todo salió bien
            // return redirect()->back()->with([
            //     'success' => 'Solicitud actualizada con éxito.',
            //     'solicitud' => $solicitud,
            // ]);

            // CHECKPOINT: Checar por qué guarda espacios en blanco en OBSERVACIONES

            return redirect()->route('solicitudes', [
                'page' => $request->input('paginaActual'),
                'searchQuery' => $request->input('searchQuery'),
                'estatusQuery' => $request->input('estatusQuery'),
                'areasQuery' => $request->input('areasQuery'),
                'fechaIngresoInicioQuery' => $request->input('fechaIngresoInicioQuery'),
                'fechaIngresoFinQuery' => $request->input('fechaIngresoFinQuery'),
            ])->with([
                'success' => 'Solicitud actualizada con éxito.',
                'solicitud' => $solicitud
            ]);
        } catch (\Exception $e) {
            // Opcional: Registra el error en storage/logs/laravel.log para que puedas depurarlo
            Log::error('Error al crear la solicitud: ' . $e->getMessage());

            // Respuesta si ocurrió un error
            return redirect()->back()
                ->withInput() // Mantiene los datos que el usuario escribió en el formulario
                ->withErrors(['error' => 'Ocurrió un error al guardar la solicitud. Por favor, inténtalo de nuevo.']);
            // O si es API: return response()->json(['message' => 'Error al procesar la solicitud', 'error' => $e->getMessage()], 500);
        }
    }
}
