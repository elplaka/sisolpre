<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth; // Asegúrate de importar Auth
use App\Models\DashboardPanelConfig; // Para la configuración de los paneles
use App\Models\Periodo; // Para la configuración de los paneles
use Illuminate\Support\Facades\DB; // Para consultas de agrupación (ej. por mes)
use Carbon\Carbon;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Log;


class AdminController extends Controller
{
    public function index(Request $request)
    {
        // 1. Obtener la configuración de los paneles desde la base de datos
        // Carga la configuración global. Si quieres por usuario, ajusta la consulta.
        // Obtener los roles del usuario autenticado como un array
        $userRoles = auth()->user()->getRoleNames()->toArray();
        $userRole = $userRoles[0] ?? null;

        $panelConfigs = DashboardPanelConfig::whereNull('user_id')
            ->orderBy('order')
            ->get();

        $currentYear = now()->year; // 2026
        $previousYear = $currentYear - 1; // 2025
        $periodOptions = [];

        // 1. Año actual
        $periodOptions[] = [
            'value' => 'anio_actual',
            'title' => 'Año Actual',
            'subtitle' => (string) $currentYear
        ];

        // 2. Año anterior
        $periodOptions[] = [
            'value' => 'anio_anterior',
            'title' => 'Año Anterior',
            'subtitle' => (string) $previousYear
        ];

        // 3. Periodo de gobierno activo
        $periodoGobierno = Periodo::where('inicio', '<=', now())
            ->where('fin', '>=', now())
            ->first();

        if ($periodoGobierno) {
            $gobiernoInicio = Carbon::parse($periodoGobierno->inicio);
            $gobiernoFin = Carbon::parse($periodoGobierno->fin);

            $currentGovernmentIndex = $gobiernoInicio->diffInYears(now());

            $indicesAGenerar = [$currentGovernmentIndex];

            if ($currentGovernmentIndex > 0) {
                $indicesAGenerar[] = $currentGovernmentIndex - 1;
            }

            foreach ($indicesAGenerar as $i) {
                $anioStart = (clone $gobiernoInicio)->addYears($i);
                $anioEnd = (clone $anioStart)->addYear()->subDay();

                if ($anioStart->greaterThan($gobiernoFin)) {
                    continue;
                }

                $numeroOrdinal = $i + 1;

                $ordinalText = match ($numeroOrdinal) {
                    1 => '1er',
                    2 => '2do',
                    3 => '3er',
                    default => "{$numeroOrdinal}o"
                };

                $startYearNum = substr((string) $anioStart->year, -2);
                $endYearNum = substr((string) $anioEnd->year, -2);

                $esActual = ($i === $currentGovernmentIndex);

                $periodOptions[] = [
                    'value' => "gobierno_anio_{$numeroOrdinal}",
                    'title' => $esActual
                        ? 'Año de Gobierno Actual'
                        : 'Año de Gobierno Anterior',
                    'subtitle' => "{$ordinalText}: Nov. '{$startYearNum} - Oct. '{$endYearNum}"
                ];
            }
        }

        // 4. Capturar el filtro seleccionado por el usuario
        $selectedPeriod = $request->input('period', 'anio_actual');

        // 5. Mapear el valor seleccionado a los rangos de fechas (StartDate / EndDate) para el Query
        [$startDate, $endDate] = match (true) {
            str_starts_with($selectedPeriod, 'gobierno_anio_') => (function () use ($selectedPeriod, $periodoGobierno) {
                $numeroOrdinal = (int) str_replace('gobierno_anio_', '', $selectedPeriod);
                $gobiernoInicio = Carbon::parse($periodoGobierno->inicio);

                // Calcular el año específico de ese año de gobierno
                $anioStart = (clone $gobiernoInicio)->addYears($numeroOrdinal - 1);
                $anioEnd = (clone $anioStart)->addYear()->subDay();

                return [$anioStart->startOfDay(), $anioEnd->endOfDay()];
            })(),

            $selectedPeriod === 'anio_anterior' => [
                Carbon::create($previousYear, 1, 1)->startOfDay(),
                Carbon::create($previousYear, 12, 31)->endOfDay()
            ],

            // Por defecto o 'anio_actual'
            default => [
                Carbon::create($currentYear, 1, 1)->startOfDay(),
                Carbon::create($currentYear, 12, 31)->endOfDay()
            ]
        };


        // 2. Preparar un array para almacenar todos los datos que los paneles necesitan
        $dataForPanels = [];
        $vuePanelConfig = []; // Configuración que se pasará a Vue para renderizar los componentes

        // 3. Iterar sobre la configuración para cargar los datos específicos de cada panel
        foreach ($panelConfigs as $config) {
            $panelData = null; // Inicializa los datos para el panel actual
            $dataKey = $config->panel_type; // La clave bajo la cual se guardarán los datos en $dataForPanels

            $limit = in_array('AUXILIAR', $userRoles) ? 8 : 7;

            switch ($config->panel_type) {
                case 'latest_solicitudes':
                    $panelData = Solicitud::with(['solicitante', 'estatus', 'localidad_beneficiada', 'user', 'area', 'solicitante.localidad'])
                        ->whereBetween('fecha', [$startDate, $endDate])
                        ->orderByDesc('id')  // 🔹 Primero por fecha más reciente
                        ->take($limit)
                        ->get();

                    break;

                case 'solicitudes_by_month':
                    // Lógica para solicitudes por mes
                    $panelData = Solicitud::select(
                        DB::raw('MONTH(fecha) as month'),
                        DB::raw('YEAR(fecha) as year'),
                        DB::raw('count(*) as total')
                    )
                        ->whereBetween('fecha', [$startDate, $endDate])
                        ->groupBy('year', 'month')
                        ->orderBy('year', 'desc')
                        ->orderBy('month', 'desc')
                        ->take(6) // Últimos 6 meses, por ejemplo
                        ->get();

                    break;

                case 'tramites_by_year':
                    // Asumiendo que "trámites" también se guardan en la tabla 'solicitudes'
                    // o que tienes otra tabla para trámites. Si es otra tabla,
                    // ajusta el modelo y la consulta. Aquí, usaré 'solicitudes' como ejemplo.

                    // $panelData = CatalogoTramite::select('catalogo_tramites.nombre_abreviado', DB::raw('COUNT(*) as total'))
                    //     ->join('solicitudes_tramites', 'catalogo_tramites.id', '=', 'solicitudes_tramites.id_tramite')
                    //     ->join('solicitudes', 'solicitudes.id', '=', 'solicitudes_tramites.id_solicitud')
                    //     ->whereYear('solicitudes.fecha_ingreso', Carbon::now()->year) // 🎯 Año dinámico
                    //     ->groupBy('catalogo_tramites.id', 'catalogo_tramites.nombre_abreviado')
                    //     ->orderByDesc('total')
                    //     ->limit(3)
                    //     ->get();

                    break;

                case 'solicitudes_by_location':
                    $panelData = Solicitud::select(
                        'localidades.nombre as localidad_nombre',
                        DB::raw('count(solicitudes.id) as total')
                    )
                        ->whereBetween('solicitudes.fecha', [$startDate, $endDate])
                        // ->join('solicitantes', 'solicitudes.id_solicitante', '=', 'solicitantes.id')
                        ->join('localidades', 'solicitudes.id_localidad_beneficiada', '=', 'localidades.id')
                        ->groupBy('localidades.nombre')
                        ->orderByDesc('total')
                        ->take(5)
                        ->get();

                    break;
                case 'people_solicitudes':
                    // Lógica para solicitudes por mes
                    $panelData = Solicitud::whereYear('fecha', now()->year)->count();

                    break;

                case 'solicitudes_totales':
                    // 1. Contar las solicitudes usando el rango dinámico
                    $totalCount = Solicitud::whereBetween('fecha', [$startDate, $endDate])->count();

                    // 2. Determinar la fecha límite para el cálculo (si el periodo sigue activo, llega hasta hoy; si ya terminó, llega hasta el fin del periodo)
                    $hoy = now();
                    $fechaFinCalculo = $hoy->lessThan($endDate) ? $hoy : $endDate;

                    $diasTranscurridos = 0;

                    if ($fechaFinCalculo->greaterThanOrEqualTo($startDate)) {
                        // Clonamos la fecha de inicio para recorrer los días de lunes a viernes
                        $iterador = (clone $startDate)->startOfDay();
                        $limite = (clone $fechaFinCalculo)->endOfDay();

                        while ($iterador->lte($limite)) {
                            // isWeekday() evalúa automáticamente si es de lunes a viernes
                            if ($iterador->isWeekday()) {
                                $diasTranscurridos++;
                            }
                            $iterador->addDay();
                        }
                    }

                    // 3. Calcular el promedio diario de lunes a viernes (evitando división por cero)
                    $promedioDiario = $diasTranscurridos > 0 ? round($totalCount / $diasTranscurridos, 1) : 0;

                    $solicitudesMesActual = Solicitud::whereBetween('fecha', [
                        now()->startOfMonth()->toDateString(),
                        now()->endOfMonth()->toDateString()
                    ])->count();

                    // 4. Retornar los datos para el componente
                    $panelData = [
                        'count' => $totalCount,
                        'promedioDiario' => $promedioDiario,
                        'mesActual' => $solicitudesMesActual
                    ];

                    break;

                case 'solicitudes_apoyo_entregado':
                    // 1. Total de solicitudes del periodo seleccionado
                    $totalSolicitudes = Solicitud::whereBetween('fecha', [$startDate, $endDate])->count();

                    // 2. Total de solicitudes aprobadas del periodo (ajustando el filtro de fechas)
                    $aprobadasCount = Solicitud::whereBetween('fecha', [$startDate, $endDate])
                        ->where('id_estatus', 3)
                        ->count();

                    // 3. Calcular porcentaje (evitando división por cero)
                    $porcentaje = $totalSolicitudes > 0 ? round(($aprobadasCount / $totalSolicitudes) * 100, 1) : 0;

                    // 4. Retornar como un objeto/arreglo con ambos datos para la tarjeta
                    $panelData = [
                        'count' => $aprobadasCount,
                        'percentage' => $porcentaje
                    ];

                    break;

                case 'solicitudes_en_tramite':
                    // 1. Total de solicitudes del periodo seleccionado
                    $totalSolicitudes = Solicitud::whereBetween('fecha', [$startDate, $endDate])->count();

                    // 2. Total de solicitudes aprobadas del periodo (ajustando el filtro de fechas)
                    $aprobadasCount = Solicitud::whereBetween('fecha', [$startDate, $endDate])
                        ->where('id_estatus', 1)
                        ->count();

                    // 3. Calcular porcentaje (evitando división por cero)
                    $porcentaje = $totalSolicitudes > 0 ? round(($aprobadasCount / $totalSolicitudes) * 100, 1) : 0;

                    // 4. Retornar como un objeto/arreglo con ambos datos para la tarjeta
                    $panelData = [
                        'count' => $aprobadasCount,
                        'percentage' => $porcentaje
                    ];

                    break;

                case 'solicitudes_aprobadas':
                    // 1. Total de solicitudes del periodo seleccionado
                    $totalSolicitudes = Solicitud::whereBetween('fecha', [$startDate, $endDate])->count();

                    // 2. Total de solicitudes aprobadas del periodo (ajustando el filtro de fechas)
                    $aprobadasCount = Solicitud::whereBetween('fecha', [$startDate, $endDate])
                        ->where('id_estatus', 2)
                        ->count();

                    // 3. Calcular porcentaje (evitando división por cero)
                    $porcentaje = $totalSolicitudes > 0 ? round(($aprobadasCount / $totalSolicitudes) * 100, 1) : 0;

                    // 4. Retornar como un objeto/arreglo con ambos datos para la tarjeta
                    $panelData = [
                        'count' => $aprobadasCount,
                        'percentage' => $porcentaje
                    ];

                    break;

                case 'solicitudes_tiempo_entrega':
                    // 1. Métrica agregada (Promedio, Máximo Días y Total)

                    $tiempoPromedioEntrega = DB::table('solicitudes')
                        ->whereBetween('fecha', [$startDate, $endDate])
                        ->where('id_estatus', 3)
                        ->whereNotNull('fecha_resolucion')
                        ->select(
                            DB::raw('ROUND(AVG(DATEDIFF(fecha_resolucion, fecha)), 1) as promedio_dias'),
                            DB::raw('MAX(DATEDIFF(fecha_resolucion, fecha)) as max_dias'),
                            DB::raw('COUNT(id) as total_concluidas')
                        )
                        ->first();

                    $maxDias = $tiempoPromedioEntrega->max_dias ?? 0;

                    // 2. Consultar las solicitudes que alcanzaron ese tiempo máximo
                    $solicitudesMax = DB::table('solicitudes')
                        ->whereBetween('fecha', [$startDate, $endDate])
                        ->where('id_estatus', 3)
                        ->whereNotNull('fecha_resolucion')
                        ->whereRaw('DATEDIFF(fecha_resolucion, fecha) = ?', [$maxDias])
                        ->pluck('id'); // Obtiene los IDs empatados

                    $panelData = [
                        'promedioDias' => $tiempoPromedioEntrega->promedio_dias ?? 0,
                        'totalConcluidas' => $tiempoPromedioEntrega->total_concluidas ?? 0,
                        'maxDias' => $maxDias,
                        'maxSolicitudFolio' => $solicitudesMax->first() ?? null, // El primer ID
                        'totalEmpatados' => $solicitudesMax->count(), // Cantidad de solicitudes con el mismo tiempo máximo
                    ];

                    break;

                case 'solicitudes_con_rezago':
                    $limiteDias = 15;
                    $fechaLimite = Carbon::now()->subDays($limiteDias);
                    $estatusEnProcesoIds = [1, 2];

                    // Total de solicitudes abiertas (En Trámite y Aprobadas) en el periodo
                    $totalAbiertas = DB::table('solicitudes')
                        ->whereBetween('fecha', [$startDate, $endDate])
                        ->whereIn('id_estatus', $estatusEnProcesoIds)
                        ->count();

                    $baseRezagoQuery = DB::table('solicitudes')
                        ->whereBetween('fecha', [$startDate, $endDate])
                        ->whereIn('id_estatus', $estatusEnProcesoIds)
                        ->where('fecha', '<=', $fechaLimite);

                    $solicitudesRezago = (clone $baseRezagoQuery)
                        ->select(
                            DB::raw('COUNT(id) as total_rezago'),
                            DB::raw('MAX(DATEDIFF(NOW(), fecha)) as max_dias_espera')
                        )
                        ->first();

                    $totalRezago = (int) ($solicitudesRezago->total_rezago ?? 0);
                    $maxDiasEspera = $solicitudesRezago->max_dias_espera ?? null;

                    $idsMaxEspera = $maxDiasEspera !== null
                        ? (clone $baseRezagoQuery)
                        ->whereRaw('DATEDIFF(NOW(), fecha) = ?', [$maxDiasEspera])
                        ->orderBy('fecha', 'asc')
                        ->orderBy('id', 'asc')
                        ->pluck('id')
                        : collect();

                    $folioMaxEspera = $idsMaxEspera->first();
                    $totalEmpatados = $idsMaxEspera->count();

                    $porcentajeRezago = $totalAbiertas > 0
                        ? round(($totalRezago / $totalAbiertas) * 100, 1)
                        : 0;

                    $panelData = [
                        'total_rezago'      => $totalRezago,
                        'total_abiertas'    => $totalAbiertas,
                        'porcentaje_rezago' => $porcentajeRezago,
                        'max_dias_espera'   => $maxDiasEspera ?? 0,
                        'folio_max_espera'  => $folioMaxEspera,
                        'total_empatados'   => $totalEmpatados,
                    ];

                    break;

                case 'solicitudes_ciudadanos_atendidos':
                    $totalCiudadanosAtendidos = DB::table('solicitudes')
                        ->whereBetween('fecha', [$startDate, $endDate])
                        ->whereNotNull('id_solicitante')
                        ->distinct('id_solicitante')
                        ->count('id_solicitante');

                    $panelData = [
                        'ciudadanos_atendidos' => $totalCiudadanosAtendidos,
                    ];

                    break;

                case 'solicitudes_monto_entregado':
                    $montoAprobado = DB::table('solicitudes')
                        ->whereIn('id_estatus', [1, 2, 3])
                        ->whereBetween('fecha', [$startDate, $endDate]) // Ajusta tu filtro de fechas o periodo según aplique
                        ->sum('cantidad_aprobada');

                    $panelData = [
                        'monto_aprobado' => $montoAprobado,
                    ];

                    break;

                case 'solicitudes_peticiones':
                    $textosPeticiones = DB::table('solicitudes')
                        ->whereBetween('fecha', [$startDate, $endDate])
                        ->whereNotNull('peticion')
                        ->pluck('peticion');

                    $stopWordsList = [
                        // Artículos, preposiciones, conectores y palabras vacías comunes
                        'el',
                        'la',
                        'los',
                        'las',
                        'un',
                        'una',
                        'unos',
                        'unas',
                        'de',
                        'del',
                        'al',
                        'a',
                        'ante',
                        'bajo',
                        'cabe',
                        'con',
                        'contra',
                        'desde',
                        'durante',
                        'en',
                        'entre',
                        'hacia',
                        'hasta',
                        'mediante',
                        'para',
                        'por',
                        'según',
                        'sin',
                        'so',
                        'sobre',
                        'tras',
                        'versus',
                        'vía',
                        'y',
                        'e',
                        'ni',
                        'o',
                        'u',
                        'que',
                        'se',
                        'no',
                        'es',
                        'lo',
                        'su',
                        'sus',
                        'como',
                        'más',
                        'mas',
                        'pero',
                        'le',
                        'les',
                        'ya',
                        'porque',
                        'cuando',
                        'muy',
                        'también',
                        'tambien',
                        'me',
                        'hay',
                        'donde',
                        'dónde',
                        'quien',
                        'quién',
                        'todo',
                        'toda',
                        'todos',
                        'todas',
                        'nos',
                        'otro',
                        'otra',
                        'otros',
                        'otras',
                        'fuese',
                        'fuera',
                        'ser',
                        'estar',
                        'haber',
                        'tener',
                        'hacer',
                        'solicitud',
                        'solicitudes',
                        'apoyo',
                        'apoyos',
                        'peticion',
                        'petición',

                        // Números en palabras (0 al 100, decenas, centenas, miles y ordinales comunes)
                        'cero',
                        'uno',
                        'una',
                        'unos',
                        'unas',
                        'dos',
                        'tres',
                        'cuatro',
                        'cinco',
                        'seis',
                        'siete',
                        'ocho',
                        'nueve',
                        'diez',
                        'once',
                        'doce',
                        'trece',
                        'catorce',
                        'quince',
                        'dieciseis',
                        'dieciséis',
                        'diecisiete',
                        'dieciocho',
                        'diecinueve',
                        'veinte',
                        'veintiun',
                        'veintiún',
                        'veintiuna',
                        'veintidos',
                        'veintidós',
                        'veintitres',
                        'veintitrés',
                        'veinticuatro',
                        'veinticinco',
                        'veintiseis',
                        'veintiséis',
                        'veintisiete',
                        'veintiocho',
                        'veintinueve',
                        'treinta',
                        'cuarenta',
                        'cincuenta',
                        'sesenta',
                        'setenta',
                        'ochenta',
                        'noventa',
                        'cien',
                        'ciento',
                        'cientos',
                        'doscientos',
                        'doscientas',
                        'trescientos',
                        'trescientas',
                        'cuatrocientos',
                        'cuatrocientas',
                        'quinientos',
                        'quinientas',
                        'seiscientos',
                        'seiscientas',
                        'setecientos',
                        'setecientas',
                        'ochocientos',
                        'ochocientas',
                        'novecientos',
                        'novecientas',
                        'mil',
                        'miles',
                        'millon',
                        'millón',
                        'millones',
                        'primero',
                        'primer',
                        'primera',
                        'primeros',
                        'primeras',
                        'segundo',
                        'segunda',
                        'segundos',
                        'segundas',
                        'tercero',
                        'tercer',
                        'tercera',
                        'terceros',
                        'terceras',
                        'cuarto',
                        'cuarta',
                        'quinto',
                        'quinta',
                        'sexto',
                        'sexta',
                        'septimo',
                        'séptimo',
                        'septima',
                        'séptima',
                        'octavo',
                        'octava',
                        'noveno',
                        'novena',
                        'decimo',
                        'décimo',
                        'decima',
                        'décima',
                    ];

                    // array_flip permite búsquedas O(1) con isset() en lugar de in_array()
                    $stopWordsMap = array_flip($stopWordsList);
                    $palabrasContadas = [];

                    foreach ($textosPeticiones as $texto) {
                        // Reemplaza cualquier carácter que no sea letra o espacio por un espacio en blanco
                        $limpio = mb_strtolower(preg_replace('/[^\p{L}\s]+/u', ' ', $texto), 'UTF-8');
                        $palabras = preg_split('/\s+/u', trim($limpio), -1, PREG_SPLIT_NO_EMPTY);

                        foreach ($palabras as $palabra) {
                            if (
                                mb_strlen($palabra, 'UTF-8') > 2 &&
                                !is_numeric($palabra) &&
                                !isset($stopWordsMap[$palabra])
                            ) {
                                $palabrasContadas[$palabra] = ($palabrasContadas[$palabra] ?? 0) + 1;
                            }
                        }
                    }

                    arsort($palabrasContadas);
                    $topPalabras = array_slice($palabrasContadas, 0, 10, true);

                    $frecuenciaPeticiones = [];
                    foreach ($topPalabras as $palabra => $frecuencia) {
                        $frecuenciaPeticiones[] = [
                            'palabra'    => mb_strtoupper($palabra, 'UTF-8'),
                            'frecuencia' => $frecuencia,
                        ];
                    }

                    $panelData = [
                        'frecuencia_peticiones' => $frecuenciaPeticiones,
                    ];
                    break;

                case 'solicitudes_by_area':
                    $panelData = Solicitud::query()
                        ->select(
                            DB::raw('COALESCE(areas.nombre, "N/A") as area'),
                            DB::raw('count(*) as total')
                        )
                        ->whereBetween('fecha', [$startDate, $endDate])
                        ->leftJoin('areas', 'solicitudes.id_area', '=', 'areas.id')
                        ->groupBy('areas.nombre')
                        // ORDENAMIENTO PERSONALIZADO: 
                        // Si el área es NULL o "N/A", le asignamos un 1 para mandarla al final; de lo contrario un 0.
                        ->orderByRaw('CASE WHEN areas.nombre IS NULL THEN 1 ELSE 0 END ASC')
                        // Después ordenamos de mayor a menor por el total de solicitudes
                        ->orderByDesc('total')
                        ->get();

                    break;

                default:
                    $panelData = ['message' => 'Contenido de panel no definido o genérico para: ' . $config->panel_type];
                    break;
            }

            // Almacenar los datos cargados bajo su respectiva clave
            $dataForPanels[$dataKey] = $panelData;

            // Construir la configuración para el frontend de Vue
            $vuePanelConfig[] = [
                'slot_key' => $config->slot_key,
                'panel_type' => $config->panel_type,
                // Convierte el 'panel_type' de snake_case a PascalCase para el nombre del componente Vue
                // Ej: 'latest_solicitudes' -> 'LatestSolicitudesPanel'
                // 'solicitudes_by_month' -> 'SolicitudesByMonthPanel'
                'component_name' => str_replace('_', '', ucwords($config->panel_type, '_')) . 'Panel',
                'data_key' => $dataKey, // La clave en $dataForPanels donde se encuentran los datos
            ];

            // break;
        }



        // 4. Renderizar el componente Inertia, pasando todas las props necesarias
        return Inertia::render('Admin/Dashboard', [ // ¡Tu componente es 'Admin/Dashboard'!
            'userAuth' => Auth::user(), // Tu prop existente
            'panelConfig' => $vuePanelConfig, // La configuración de los paneles
            'panelData' => $dataForPanels,   // Los datos para cada panel
            'userRole' => $userRole, // El rol del usuario autenticado
            'periodOptions' => $periodOptions,
            'filters' => [
                'period' => $selectedPeriod
            ]
        ]);
    }
}
