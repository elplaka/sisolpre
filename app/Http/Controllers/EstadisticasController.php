<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\SettingsHelper;
use App\Models\Periodo;
use App\Models\Solicitud; // Para el panel de últimas solicitudes
use App\Models\CatalogoTramite; // Para el panel de últimas solicitudes
use Illuminate\Support\Facades\DB; // Para consultas de agrupación (ej. por mes)
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class EstadisticasController extends Controller
{
    //
    public function index(Request $request)
    {
        // dd($request->all());

        // Obtener el ID del periodo actual
        $periodoId = SettingsHelper::get('periodo_actual');

        // Usar el ID para buscar el objeto completo
        $periodoActual = Periodo::find($periodoId);

        $currentYear = now()->year; // O date('Y')

        $fechaInicioQuery = $request->input('fechaInicioQuery') ?: "{$currentYear}-01-01";
        $fechaFinQuery = $request->input('fechaFinQuery') ?: "{$currentYear}-12-31";

        if ($request->boolean('desdeSideBar')) {

            // Si no viene fecha de inicio, asignamos el 1 de enero del año actual
            $fechaInicioQuery = $fechaInicioQuery ?? "{$currentYear}-01-01";

            // Si no viene fecha fin, asignamos el 31 de diciembre del año actual
            $fechaFinQuery = $fechaFinQuery ?? "{$currentYear}-12-31";
        }


        $currentPeriod = $request->input('currentPeriod');
        if ($currentPeriod) {
            $inicioOriginal = $periodoActual['inicio']; // "2024-11-01"
            $finOriginal = $periodoActual['fin'];       // "2027-10-31"

            if ($currentPeriod == 'gobierno_anio_1') {
                $fechaInicioQuery = Carbon::parse($inicioOriginal)->format('Y-m-d'); // "2025-11-01"
                $fechaFinQuery = Carbon::parse($finOriginal)->subYears(2)->format('Y-m-d');
            } elseif ($currentPeriod == 'gobierno_anio_2') {
                $fechaInicioQuery = Carbon::parse($inicioOriginal)->addYear()->format('Y-m-d'); // "2025-11-01"
                $fechaFinQuery = Carbon::parse($finOriginal)->subYears(1)->format('Y-m-d');
            } else if ($currentPeriod == 'anio_anterior') {
                $anioAnterior = $currentYear - 1; // Ejemplo: 2026 - 1 = 2025
                $fechaInicioQuery = "{$anioAnterior}-01-01"; // "2025-01-01"
                $fechaFinQuery = "{$anioAnterior}-12-31";     // "2025-12-31"
            }
        }

        $estadisticaSeleccionada = $request->input('estadisticaSeleccionada');

        $rawDataFromBackend = null;
        if ($estadisticaSeleccionada == 'solicitudes_mes') {
            // Configurar el idioma de la sesión de MySQL a español para que %M devuelva el mes en español
            DB::statement("SET lc_time_names = 'es_MX'");

            $rawDataFromBackend = Solicitud::query()
                ->whereBetween('fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->selectRaw("
                DATE_FORMAT(fecha, '%Y-%m') as periodo_key,
                DATE_FORMAT(fecha, '%M %Y') as mes,
                COUNT(*) as total,
                SUM(CASE WHEN id_estatus = 1 THEN 1 ELSE 0 END) as enTramite,
                SUM(CASE WHEN id_estatus = 2 THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN id_estatus = 3 THEN 1 ELSE 0 END) as apoyoEntregado,
                SUM(CASE WHEN id_estatus = 4 THEN 1 ELSE 0 END) as canceladas
            ")
                ->groupBy('periodo_key', 'mes')
                ->orderBy('periodo_key', 'asc')
                ->get()
                ->keyBy('periodo_key'); // Indexamos por la llave 'YYYY-MM' para búsqueda rápida

            // 2. Crear el periodo completo de meses entre la fecha de inicio y la fecha de fin
            $inicio = Carbon::parse($fechaInicioQuery)->startOfMonth();
            $fin = Carbon::parse($fechaFinQuery)->startOfMonth();

            $periodoCompleto = CarbonPeriod::create($inicio, '1 month', $fin);

            // 3. Mapear para asegurar que ningún mes falte (si no hay datos, rellenar con ceros)
            $dataFinal = collect($periodoCompleto)->map(function (Carbon $date) use ($rawDataFromBackend) {

                $periodoKey = $date->format('Y-m');

                $abreviaturasMeses = [
                    1 => 'Ene',
                    2 => 'Feb',
                    3 => 'Mar',
                    4 => 'Abr',
                    5 => 'May',
                    6 => 'Jun',
                    7 => 'Jul',
                    8 => 'Ago',
                    9 => 'Sep',
                    10 => 'Oct',
                    11 => 'Nov',
                    12 => 'Dic',
                ];

                // $nombreMes = $abreviaturasMeses[$date->month] . " '" . substr($date->year, -2);
                $nombreMes = $abreviaturasMeses[$date->month];

                $registro = $rawDataFromBackend->get($periodoKey);

                return [
                    'periodo_key'    => $periodoKey,
                    'mes'            => $nombreMes,
                    'total'          => $registro->total ?? 0,
                    'enTramite'      => $registro->enTramite ?? 0,
                    'aprobadas'      => $registro->aprobadas ?? 0,
                    'apoyoEntregado' => $registro->apoyoEntregado ?? 0,
                    'canceladas'     => $registro->canceladas ?? 0,
                ];
            })->values();

            // Calcular días analizados en el periodo para el promedio diario exacto
            $inicio = Carbon::parse($fechaInicioQuery);
            $fin = Carbon::parse($fechaFinQuery);

            $diasHabiles = $inicio->diffInDaysFiltered(function (Carbon $date) {
                return !$date->isWeekend();
            }, $fin->copy()->addDay()); // addDay() asegura incluir el día final en la cuenta

            // Evitamos división entre cero en caso de un periodo de solo fin de semana
            $diasHabiles = max(1, $diasHabiles);

            // 2. Contar solicitudes considerando únicamente de Lunes a Viernes
            // En MySQL/MariaDB: WEEKDAY() regresa 0=Lunes, 1=Martes, ..., 4=Viernes, 5=Sábado, 6=Domingo
            $totalSolicitudesPeriodo = Solicitud::query()
                ->whereBetween('fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->whereRaw('WEEKDAY(fecha) < 5') // 0 a 4 corresponden de Lunes a Viernes
                ->count();

            // 3. Promedio diario en días hábiles
            $promedioDiario = round($totalSolicitudesPeriodo / $diasHabiles, 2);

            $demandaPorDia = Solicitud::query()
                ->whereBetween('fecha', [
                    $fechaInicioQuery . ' 00:00:00',
                    $fechaFinQuery . ' 23:59:59'
                ])
                ->select(
                    DB::raw("DATE(fecha) as fecha_demanda"),
                    DB::raw("COUNT(*) as total_solicitudes")
                )
                ->groupBy('fecha_demanda')
                ->orderByDesc('total_solicitudes')
                ->get();

            $diaMayorDemanda = null;

            if ($demandaPorDia->isNotEmpty()) {

                Carbon::setLocale('es');

                $maximoSolicitudes = (int) $demandaPorDia->first()->total_solicitudes;

                // Solo se considera pico cuando hay al menos 2 solicitudes
                if ($maximoSolicitudes >= 2) {

                    $diasPico = $demandaPorDia
                        ->where('total_solicitudes', $maximoSolicitudes)
                        ->values();

                    $diasPicoFormatted = $diasPico->map(function ($dia) {

                        $fechaCarbon = Carbon::parse($dia->fecha_demanda);

                        $diaSemana = ucfirst(
                            str_replace('.', '', $fechaCarbon->translatedFormat('D'))
                        );

                        $diaNumero = $fechaCarbon->format('d');

                        $mesNombre = ucfirst(
                            $fechaCarbon->translatedFormat('M')
                        );

                        $anio = $fechaCarbon->format('Y');

                        return [
                            'fecha_demanda' => "{$diaSemana}, {$diaNumero} {$mesNombre} {$anio}",
                            'total_solicitudes' => (int) $dia->total_solicitudes,
                        ];
                    })->values();

                    $diaMayorDemanda = [
                        'total_solicitudes' => $maximoSolicitudes,
                        'total_dias_pico' => $diasPicoFormatted->count(),
                        'fecha_principal' => $diasPicoFormatted->first()['fecha_demanda'],
                        'dias_pico' => $diasPicoFormatted,
                    ];
                } else {

                    // No existe una concentración suficiente para llamarlo "pico"
                    $diaMayorDemanda = [
                        'total_solicitudes' => $maximoSolicitudes,
                        'total_dias_pico' => 0,
                        'fecha_principal' => null,
                        'dias_pico' => [],
                        'actividad_distribuida' => true,
                    ];
                }
            }

            // 3. DISTRIBUCIÓN POR DÍA DE LA SEMANA (Lunes a Domingo)
            DB::statement("SET lc_time_names = 'es_MX'");
            $distribucionDiasSemana = Solicitud::query()
                ->whereBetween('fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->select(
                    DB::raw("DAYNAME(fecha) as dia_semana"),
                    DB::raw("DAYOFWEEK(fecha) as dia_index"), // 1 = Domingo, 2 = Lunes, etc.
                    DB::raw("COUNT(*) as total")
                )
                ->groupBy('dia_semana', 'dia_index')
                ->orderBy('dia_index', 'asc')
                ->get();


            return inertia('Estadisticas/Index', [
                'userAuth' => $request->user(),
                'kpisSolicitudes' => [
                    'promedio_diario' => $promedioDiario,
                    'dia_mayor_demanda' => $diaMayorDemanda,
                    'distribucion_dias_semana' => $distribucionDiasSemana
                ],
                'periodoActual' => $periodoActual,
                'fechaInicioQuery' => $fechaInicioQuery,
                'fechaFinQuery' => $fechaFinQuery,
                'solicitudes' => $dataFinal,
                'estadisticaSeleccionada' => $estadisticaSeleccionada,
            ]);
        } else if ($estadisticaSeleccionada == 'atencion_ciudadana') {
            // 1. MÉTRICAS GENERALES: Total de personas atendidas y promedio diario
            $totalPersonasAtendidas = Solicitud::query()
                ->whereBetween('fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->distinct('id_solicitante')
                ->count('id_solicitante');

            // 2. Total de solicitudes válidas (con los estatus 1, 2, 3 en el rango)
            $totalSolicitudesValidas = Solicitud::query()
                ->whereBetween('fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->count();

            // 3. Promedio de solicitudes por persona (evitando división por cero)
            $promedioSolicitudesPorPersona = $totalPersonasAtendidas > 0
                ? round($totalSolicitudesValidas / $totalPersonasAtendidas, 2)
                : 0;

            $totalCiudadanosRecurrentes = DB::table('solicitudes')
                ->select('id_solicitante')
                ->whereNotNull('id_solicitante')
                ->where('id_estatus', '!=', 4)
                ->whereBetween('fecha', [
                    $fechaInicioQuery . ' 00:00:00',
                    $fechaFinQuery . ' 23:59:59'
                ])
                ->groupBy('id_solicitante')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();

            $porcentajeCiudadanosRecurrentes = $totalPersonasAtendidas > 0
                ? round(
                    ($totalCiudadanosRecurrentes / $totalPersonasAtendidas) * 100,
                    1
                )
                : 0;

            // 4. SOLICITANTES CON MÁS DE UNA SOLICITUD (Ciudadanos reincidentes / recurrentes - SIN ESTATUS)
            $ciudadanosReincidentes = Solicitud::query()
                ->whereBetween('solicitudes.fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->join('solicitantes', 'solicitudes.id_solicitante', '=', 'solicitantes.id')
                ->select(
                    'solicitantes.id as id_solicitante',
                    DB::raw("CONCAT(solicitantes.nombre, ' ', solicitantes.apellidos) as nombre_completo"),
                    DB::raw("COALESCE(solicitantes.curp, CONCAT(solicitantes.nombre, ' ', solicitantes.apellidos)) as llave_agrupacion"),
                    DB::raw("COUNT(solicitudes.id) as total_solicitudes")
                )
                // Agrupamos usando COALESCE: Si la CURP existe y no es nula, agrupa por CURP; si es NULL, agrupa por Nombre y Apellidos
                ->groupBy('solicitantes.id', 'solicitantes.nombre', 'solicitantes.apellidos', 'solicitantes.curp')
                ->having('total_solicitudes', '>', 1)
                ->orderByDesc('total_solicitudes')
                ->get();

            // DISTRIBUCIÓN DE CIUDADANOS SEGÚN SU NÚMERO DE SOLICITUDES EN EL PERIODO
            $distribucionSolicitudesPorCiudadano = DB::query()
                ->fromSub(function ($query) use ($fechaInicioQuery, $fechaFinQuery) {
                    $query->from('solicitudes')
                        ->select('id_solicitante')
                        ->selectRaw('COUNT(*) AS total_solicitudes')
                        ->whereNotNull('id_solicitante')
                        ->whereBetween('fecha', [
                            $fechaInicioQuery . ' 00:00:00',
                            $fechaFinQuery . ' 23:59:59',
                        ])
                        ->groupBy('id_solicitante');
                }, 'distribucion')
                ->select('total_solicitudes')
                ->selectRaw('COUNT(*) AS total_ciudadanos')
                ->groupBy('total_solicitudes')
                ->orderBy('total_solicitudes')
                ->get();

            $solicitudesAtencionPeriodo = DB::table('solicitudes')
                ->select(
                    'id_solicitante',
                    'fecha'
                )
                ->whereNotNull('id_solicitante')
                ->whereBetween('fecha', [
                    $fechaInicioQuery . ' 00:00:00',
                    $fechaFinQuery . ' 23:59:59',
                ])
                ->orderBy('fecha')
                ->orderBy('id')
                ->get();

            $solicitantesVistos = [];

            $evolucionAtencionCiudadana = [];

            foreach ($solicitudesAtencionPeriodo as $solicitud) {
                $periodo = Carbon::parse($solicitud->fecha)->format('Y-m');

                if (!isset($evolucionAtencionCiudadana[$periodo])) {
                    $evolucionAtencionCiudadana[$periodo] = [
                        'periodo' => $periodo,
                        'total_solicitudes' => 0,
                        'ciudadanos_nuevos' => 0,
                        'ciudadanos_recurrentes' => 0,
                    ];
                }

                $evolucionAtencionCiudadana[$periodo]['total_solicitudes']++;

                if (isset($solicitantesVistos[$solicitud->id_solicitante])) {
                    $evolucionAtencionCiudadana[$periodo]['ciudadanos_recurrentes']++;
                } else {
                    $solicitantesVistos[$solicitud->id_solicitante] = true;

                    $evolucionAtencionCiudadana[$periodo]['ciudadanos_nuevos']++;
                }
            }

            $inicio = Carbon::parse($fechaInicioQuery)->startOfMonth();
            $fin = Carbon::parse($fechaFinQuery)->startOfMonth();

            $evolucionAtencionCiudadanaFinal = collect();

            foreach (CarbonPeriod::create($inicio, '1 month', $fin) as $mes) {
                $periodo = $mes->format('Y-m');

                $registro = $evolucionAtencionCiudadana[$periodo] ?? null;

                $evolucionAtencionCiudadanaFinal->push([
                    'periodo' => $periodo,
                    'total_solicitudes' => $registro['total_solicitudes'] ?? 0,
                    'ciudadanos_nuevos' => $registro['ciudadanos_nuevos'] ?? 0,
                    'ciudadanos_recurrentes' => $registro['ciudadanos_recurrentes'] ?? 0,
                ]);
            }

            return inertia('Estadisticas/Index', [
                'kpisAtencionCiudadana' => [
                    'total_personas' => $totalPersonasAtendidas,
                    'promedio_solicitudes_persona' => $promedioSolicitudesPorPersona,
                    'total_ciudadanos_recurrentes' => $totalCiudadanosRecurrentes,
                    'porcentaje_ciudadanos_recurrentes' => $porcentajeCiudadanosRecurrentes,
                    'distribucion_solicitudes_ciudadano' => $distribucionSolicitudesPorCiudadano,
                    'evolucion_atencion_ciudadana' => $evolucionAtencionCiudadanaFinal,
                    'total_solicitudes' => $totalSolicitudesValidas
                ],
                'rawDataFromBackend' => $ciudadanosReincidentes,
                'userAuth' => $request->user(),
                'periodoActual' => $periodoActual,
                'fechaInicioQuery' => $fechaInicioQuery,
                'fechaFinQuery' => $fechaFinQuery,
                'estadisticaSeleccionada' => $estadisticaSeleccionada,
            ]);
        } else if ($estadisticaSeleccionada == 'recursos_publicos') {
            $tipoApoyoQuery = $request->input('idTipoApoyo');

            // Aquí conserva todas tus consultas actuales.
            $rawDataFromBackend = Solicitud::query()
                ->whereBetween('fecha', [
                    $fechaInicioQuery . ' 00:00:00',
                    $fechaFinQuery . ' 23:59:59'
                ])
                ->where('id_estatus', '!=', 4)

                ->when(
                    $tipoApoyoQuery !== null && $tipoApoyoQuery !== '',
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('id_tipo_apoyo', $tipoApoyoQuery);
                    }
                )
                ->selectRaw("
                    DATE_FORMAT(fecha, '%Y-%m') as periodo_key,

                    CONCAT(
                        CASE MONTH(fecha)
                            WHEN 1 THEN 'Enero'
                            WHEN 2 THEN 'Febrero'
                            WHEN 3 THEN 'Marzo'
                            WHEN 4 THEN 'Abril'
                            WHEN 5 THEN 'Mayo'
                            WHEN 6 THEN 'Junio'
                            WHEN 7 THEN 'Julio'
                            WHEN 8 THEN 'Agosto'
                            WHEN 9 THEN 'Septiembre'
                            WHEN 10 THEN 'Octubre'
                            WHEN 11 THEN 'Noviembre'
                            WHEN 12 THEN 'Diciembre'
                        END,
                        ' ',
                        YEAR(fecha)
                    ) as mes,

                    CONCAT(
                        CASE MONTH(fecha)
                            WHEN 1 THEN 'Ene'
                            WHEN 2 THEN 'Feb'
                            WHEN 3 THEN 'Mar'
                            WHEN 4 THEN 'Abr'
                            WHEN 5 THEN 'May'
                            WHEN 6 THEN 'Jun'
                            WHEN 7 THEN 'Jul'
                            WHEN 8 THEN 'Ago'
                            WHEN 9 THEN 'Sep'
                            WHEN 10 THEN 'Oct'
                            WHEN 11 THEN 'Nov'
                            WHEN 12 THEN 'Dic'
                        END,
                        ' ',
                        YEAR(fecha)
                    ) as mes_corto,

                    SUM(COALESCE(cantidad_aprobada, 0)) as total_monto_aprobado,
                    COUNT(id) as total_solicitudes,
                    COUNT(CASE WHEN id_estatus = 3 THEN 1 END) as total_solicitudes_entregado,
                    COUNT(CASE WHEN id_estatus IN (1, 2) THEN 1 END) as total_solicitudes_pendiente,

                    SUM(
                        CASE WHEN id_estatus = 3
                        THEN COALESCE(cantidad_aprobada, 0)
                        ELSE 0 END
                    ) as monto_entregado,

                    SUM(
                        CASE WHEN id_estatus IN (1, 2)
                        THEN COALESCE(cantidad_aprobada, 0)
                        ELSE 0 END
                    ) as monto_pendiente_entrega,

                    COUNT(DISTINCT id_solicitante) as total_ciudadanos,

                    ROUND(
                        SUM(COALESCE(cantidad_aprobada, 0)) /
                        NULLIF(COUNT(DISTINCT id_solicitante), 0),
                        2
                    ) as promedio_monto_aprobado_ciudadano,

                    COUNT(
                        DISTINCT CASE WHEN id_estatus = 3
                        THEN id_solicitante END
                    ) as ciudadanos_con_apoyo_entregado,

                    ROUND(
                        SUM(
                            CASE WHEN id_estatus = 3
                            THEN COALESCE(cantidad_aprobada, 0)
                            ELSE 0 END
                        ) /
                        NULLIF(
                            COUNT(DISTINCT CASE WHEN id_estatus = 3
                            THEN id_solicitante END),
                            0
                        ),
                        2
                    ) as promedio_monto_entregado_ciudadano,

                    COUNT(
                        DISTINCT CASE WHEN id_estatus IN (1, 2)
                        THEN id_solicitante END
                    ) as ciudadanos_con_apoyo_pendiente,

                    ROUND(
                        SUM(
                            CASE WHEN id_estatus IN (1, 2)
                            THEN COALESCE(cantidad_aprobada, 0)
                            ELSE 0 END
                        ) /
                        NULLIF(
                            COUNT(DISTINCT CASE WHEN id_estatus IN (1, 2)
                            THEN id_solicitante END),
                            0
                        ),
                        2
                    ) as promedio_monto_pendiente_ciudadano
                ")
                ->groupBy('periodo_key', 'mes', 'mes_corto')
                ->orderBy('periodo_key', 'asc')
                ->get()
                ->keyBy('periodo_key');

            // --------------------------------------------------
            // COMPLETAR MESES SIN REGISTROS
            // --------------------------------------------------

            $meses = [
                1 => ['Enero', 'Ene'],
                2 => ['Febrero', 'Feb'],
                3 => ['Marzo', 'Mar'],
                4 => ['Abril', 'Abr'],
                5 => ['Mayo', 'May'],
                6 => ['Junio', 'Jun'],
                7 => ['Julio', 'Jul'],
                8 => ['Agosto', 'Ago'],
                9 => ['Septiembre', 'Sep'],
                10 => ['Octubre', 'Oct'],
                11 => ['Noviembre', 'Nov'],
                12 => ['Diciembre', 'Dic'],
            ];

            $fechaInicio = \Carbon\Carbon::parse($fechaInicioQuery)->startOfMonth();
            $fechaFin = \Carbon\Carbon::parse($fechaFinQuery)->startOfMonth();

            $datosCompletos = collect();

            while ($fechaInicio->lte($fechaFin)) {

                $periodoKey = $fechaInicio->format('Y-m');

                if ($rawDataFromBackend->has($periodoKey)) {

                    $datosCompletos->push(
                        $rawDataFromBackend->get($periodoKey)
                    );
                } else {

                    $numeroMes = (int) $fechaInicio->format('n');
                    $anio = $fechaInicio->format('Y');

                    $datosCompletos->push([
                        'periodo_key' => $periodoKey,
                        'mes' => $meses[$numeroMes][0] . ' ' . $anio,
                        'mes_corto' => $meses[$numeroMes][1] . ' ' . $anio,

                        'total_monto_aprobado' => 0,
                        'monto_entregado' => 0,
                        'monto_pendiente_entrega' => 0,

                        'total_ciudadanos' => 0,
                        'promedio_monto_aprobado_ciudadano' => 0,

                        'ciudadanos_con_apoyo_entregado' => 0,
                        'promedio_monto_entregado_ciudadano' => 0,

                        'ciudadanos_con_apoyo_pendiente' => 0,
                        'promedio_monto_pendiente_ciudadano' => 0,
                    ]);
                }

                $fechaInicio->addMonth();
            }

            $rawDataFromBackend = $datosCompletos->values();

            $criterioOrdenLocalidades = $request->input('criterioOrdenLocalidades', 'total');

            $columnaOrden = match ($criterioOrdenLocalidades) {
                'total'       => 'total_monto_aprobado',
                'entregado'   => 'monto_entregado',
                'pendiente'   => 'monto_pendiente_entrega',
                'volumen'     => 'total_solicitudes',
                default       => 'monto_entregado',
            };

            $rawDataLocalidades = Solicitud::query()
                ->join('localidades', 'solicitudes.id_localidad_beneficiada', '=', 'localidades.id')
                ->whereBetween('solicitudes.fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->where('solicitudes.id_estatus', '!=', 4)
                ->when(
                    $tipoApoyoQuery !== null,
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('solicitudes.id_tipo_apoyo', $tipoApoyoQuery);
                    }
                )
                ->selectRaw("
                localidades.id as localidad_id,
                localidades.nombre as localidad_nombre,
                
                -- Montos Financieros
                SUM(COALESCE(solicitudes.cantidad_aprobada, 0)) as total_monto_aprobado,
                SUM(CASE WHEN solicitudes.id_estatus = 3 THEN COALESCE(solicitudes.cantidad_aprobada, 0) ELSE 0 END) as monto_entregado,
                SUM(CASE WHEN solicitudes.id_estatus IN (1, 2) THEN COALESCE(solicitudes.cantidad_aprobada, 0) ELSE 0 END) as monto_pendiente_entrega,

                -- Conteos de Solicitudes (Nuevos campos)
                COUNT(solicitudes.id) as total_solicitudes,
                COUNT(CASE WHEN solicitudes.id_estatus = 3 THEN 1 END) as total_solicitudes_entregadas,
                COUNT(CASE WHEN solicitudes.id_estatus IN (1, 2) THEN 1 END) as total_solicitudes_pendientes
            ")
                ->groupBy('localidades.id', 'localidades.nombre')
                ->having('total_monto_aprobado', '>', 0)
                ->orderByDesc($columnaOrden)
                ->when($columnaOrden !== 'total_monto_aprobado', function ($query) {
                    $query->orderByDesc('total_monto_aprobado');
                })
                ->orderBy('localidades.nombre', 'asc')
                ->get();

            $criterioOrdenAreas = $request->input('criterioOrdenAreas', 'total');

            $columnaOrden = match ($criterioOrdenAreas) {
                'total'       => 'total_monto_aprobado',
                'entregado'   => 'monto_entregado',
                'pendiente'   => 'monto_pendiente_entrega',
                'volumen'     => 'total_solicitudes',
                default       => 'monto_entregado',
            };

            $rawDataAreas = Solicitud::query()
                ->leftJoin('areas', 'solicitudes.id_area', '=', 'areas.id') // Usamos leftJoin para no perder los nulos
                ->whereBetween('solicitudes.fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->where('solicitudes.id_estatus', '!=', 4)
                ->when(
                    $tipoApoyoQuery !== null,
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('solicitudes.id_tipo_apoyo', $tipoApoyoQuery);
                    }
                )
                ->selectRaw("
                COALESCE(areas.id, 0) as area_id,
                COALESCE(areas.nombre, 'S/A') as area_nombre, -- Texto ejecutiva y amigablemente formateado

                -- Montos Financieros
                SUM(COALESCE(solicitudes.cantidad_aprobada, 0)) as total_monto_aprobado,
                SUM(CASE WHEN solicitudes.id_estatus = 3 THEN COALESCE(solicitudes.cantidad_aprobada, 0) ELSE 0 END) as monto_entregado,
                SUM(CASE WHEN solicitudes.id_estatus IN (1, 2) THEN COALESCE(solicitudes.cantidad_aprobada, 0) ELSE 0 END) as monto_pendiente_entrega,

                -- Conteos de Solicitudes
                COUNT(solicitudes.id) as total_solicitudes,
                COUNT(CASE WHEN solicitudes.id_estatus = 3 THEN 1 END) as total_solicitudes_entregadas,
                COUNT(CASE WHEN solicitudes.id_estatus IN (1, 2) THEN 1 END) as total_solicitudes_pendientes
            ")
                ->groupBy('area_id', 'area_nombre')
                ->having('total_monto_aprobado', '>', 0)
                ->orderByDesc($columnaOrden)
                ->when($columnaOrden !== 'total_monto_aprobado', function ($query) {
                    $query->orderByDesc('total_monto_aprobado');
                })
                ->orderBy('areas.nombre', 'asc')
                ->get();

            $rawDataEstatus = Solicitud::query()
                ->leftJoin('estatus_solicitudes', 'solicitudes.id_estatus', '=', 'estatus_solicitudes.id')
                ->whereBetween('solicitudes.fecha', [
                    $fechaInicioQuery . ' 00:00:00',
                    $fechaFinQuery . ' 23:59:59'
                ])
                ->when(
                    $tipoApoyoQuery !== null,
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('solicitudes.id_tipo_apoyo', $tipoApoyoQuery);
                    }
                )
                ->selectRaw("
                    COALESCE(estatus_solicitudes.id, 0) as estatus_id,
                    COALESCE(estatus_solicitudes.nombre, 'S/A') as estatus_nombre,

                    SUM(COALESCE(solicitudes.cantidad_aprobada, 0)) as total_monto_aprobado,

                    SUM(CASE WHEN solicitudes.id_estatus = 4
                        THEN COALESCE(solicitudes.cantidad_aprobada, 0) ELSE 0 END) as monto_canceladas,

                    SUM(CASE WHEN solicitudes.id_estatus = 3
                        THEN COALESCE(solicitudes.cantidad_aprobada, 0) ELSE 0 END) as monto_apoyo_entregado,

                    SUM(CASE WHEN solicitudes.id_estatus = 2
                        THEN COALESCE(solicitudes.cantidad_aprobada, 0) ELSE 0 END) as monto_aprobadas,

                    SUM(CASE WHEN solicitudes.id_estatus = 1
                        THEN COALESCE(solicitudes.cantidad_aprobada, 0) ELSE 0 END) as monto_en_tramite,

                    COUNT(solicitudes.id) as total_solicitudes
                ")
                ->groupBy('estatus_id', 'estatus_nombre')
                ->having('total_monto_aprobado', '>', 0)
                ->orderBy('estatus_id')
                ->get();

            $totalPersonasAtendidas = Solicitud::query()
                ->whereBetween('fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->distinct('id_solicitante')
                ->when(
                    $tipoApoyoQuery !== null,
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('solicitudes.id_tipo_apoyo', $tipoApoyoQuery);
                    }
                )
                ->count('id_solicitante');

            $montoTotal = Solicitud::query()
                ->whereBetween('fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->whereIn('id_estatus', [1, 2, 3])
                ->when(
                    $tipoApoyoQuery !== null,
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('solicitudes.id_tipo_apoyo', $tipoApoyoQuery);
                    }
                )
                ->sum('cantidad_aprobada');

            $montoTotalEntregado = Solicitud::query()
                ->whereBetween('fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->whereIn('id_estatus', [3])
                ->when(
                    $tipoApoyoQuery !== null,
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('solicitudes.id_tipo_apoyo', $tipoApoyoQuery);
                    }
                )
                ->sum('cantidad_aprobada');

            $montoTotalPendiente = Solicitud::query()
                ->whereBetween('fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->whereIn('id_estatus', [1, 2])
                ->when(
                    $tipoApoyoQuery !== null,
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('solicitudes.id_tipo_apoyo', $tipoApoyoQuery);
                    }
                )
                ->sum('cantidad_aprobada');

            // 1. Creamos la consulta base con el filtro de fechas
            $query = Solicitud::query()
                ->whereBetween('fecha', [$fechaInicioQuery . ' 00:00:00', $fechaFinQuery . ' 23:59:59'])
                ->when(
                    $tipoApoyoQuery !== null,
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('solicitudes.id_tipo_apoyo', $tipoApoyoQuery);
                    }
                );

            $totalSolicitudes = (clone $query)->whereIn('id_estatus', [1, 2, 3])->count();

            // 2. KPIs de Recursos Públicos (clonando la consulta base)
            $solicitudesAVG = (clone $query)->select(
                DB::raw("SUM(CASE WHEN id_estatus IN (1, 2, 3) THEN cantidad_aprobada ELSE 0 END) / " . ($totalSolicitudes >  0 ? $totalSolicitudes : 1) . " as monto_promedio_aprobado"),
                DB::raw("SUM(CASE WHEN id_estatus = 3 THEN cantidad_aprobada ELSE 0 END) / " . ($totalSolicitudes > 0 ? $totalSolicitudes : 1) . " as monto_promedio_entregado"),
                DB::raw("SUM(CASE WHEN id_estatus IN (1, 2) THEN cantidad_aprobada ELSE 0 END) / " . ($totalSolicitudes > 0 ? $totalSolicitudes : 1) . " as monto_promedio_pendiente")
            )->first()->toArray();

            $solicitudesAVG['monto_promedio_aprobado'] = round($solicitudesAVG['monto_promedio_aprobado'], 2);
            $solicitudesAVG['monto_promedio_pendiente'] = round($solicitudesAVG['monto_promedio_pendiente'], 2);
            $solicitudesAVG['monto_promedio_aprobado'] = round(
                $solicitudesAVG['monto_promedio_entregado'] +
                    $solicitudesAVG['monto_promedio_pendiente'],
                2
            );

            $query = Solicitud::query()
                ->with('estatus', 'solicitante')
                ->whereBetween('fecha', [
                    $fechaInicioQuery . ' 00:00:00',
                    $fechaFinQuery . ' 23:59:59'
                ])
                ->when(
                    $tipoApoyoQuery !== null,
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('solicitudes.id_tipo_apoyo', $tipoApoyoQuery);
                    }
                )
                ->whereIn('id_estatus', [1, 2, 3]);

            // Obtener los importes máximo y mínimo
            $montoMaximo = (clone $query)->max('cantidad_aprobada');
            $montoMinimo = (clone $query)->min('cantidad_aprobada');

            // Obtener todas las solicitudes con el monto máximo
            // Total de solicitudes en el periodo sin monto aprobado (NULL)
            $totalSinMontoAprobado = (clone $query)
                ->whereNull('cantidad_aprobada')
                ->count();

            // Solicitudes con el monto máximo (o las que tienen NULL si no hay ningún monto definido)
            $solicitudesMontoMaximo = $montoMaximo !== null
                ? (clone $query)
                ->where('cantidad_aprobada', $montoMaximo)
                ->get()
                : (clone $query)
                ->whereNull('cantidad_aprobada')
                ->get();

            // Solicitudes con el monto mínimo (o las que tienen NULL si no hay ningún monto definido)
            $solicitudesMontoMinimo = $montoMinimo !== null
                ? (clone $query)
                ->where('cantidad_aprobada', $montoMinimo)
                ->get()
                : (clone $query)
                ->whereNull('cantidad_aprobada')
                ->get();


            $topSolicitudes = Solicitud::query()
                ->with('solicitante', 'localidad_beneficiada')
                ->whereBetween('fecha', [
                    $fechaInicioQuery . ' 00:00:00',
                    $fechaFinQuery . ' 23:59:59'
                ])
                ->where('id_estatus', '!=', 4) // Excluye las canceladas
                ->when(
                    $tipoApoyoQuery !== null,
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('id_tipo_apoyo', $tipoApoyoQuery);
                    }
                )
                ->orderBy('cantidad_aprobada', 'desc') // Ordena de mayor a menor
                ->limit(10) // Limita estrictamente al Top 10
                ->get();

            $distribucionMontos = Solicitud::query()
                ->whereBetween('fecha', [
                    $fechaInicioQuery . ' 00:00:00',
                    $fechaFinQuery . ' 23:59:59'
                ])
                ->where('id_estatus', '!=', 4) // Excluye las canceladas
                ->when(
                    $tipoApoyoQuery !== null,
                    function ($query) use ($tipoApoyoQuery) {
                        $query->where('id_tipo_apoyo', $tipoApoyoQuery);
                    }
                )
                ->selectRaw("
                    COUNT(CASE WHEN COALESCE(cantidad_aprobada, 0) < 1000 THEN 1 END) AS menos_1000,
                    COUNT(CASE WHEN cantidad_aprobada >= 1000 AND cantidad_aprobada < 3000 THEN 1 END) AS entre_1000_3000,
                    COUNT(CASE WHEN cantidad_aprobada >= 3000 AND cantidad_aprobada <= 5000 THEN 1 END) AS entre_3000_5000,
                    COUNT(CASE WHEN cantidad_aprobada > 5000 THEN 1 END) AS mas_5000
                ")
                ->first();


            // 5. Estructura que envías a Inertia
            $kpisRecursosPublicos = [
                'total_ciudadanos_recurrentes' => $totalCiudadanosRecurrentes ?? 0,
                'porcentaje_ciudadanos_recurrentes' => $porcentajeCiudadanosRecurrentes ?? 0,
                'distribucion_solicitudes_ciudadano' => $distribucionSolicitudesPorCiudadano ?? [],
                'evolucion_atencion_ciudadana' => $evolucionAtencionCiudadanaFinal ?? [],
                'total_solicitudes' => $totalSolicitudes,
                'solicitudes_avg' => $solicitudesAVG,
                'monto_total' => $montoTotal,
                'monto_total_entregado' => $montoTotalEntregado,
                'monto_total_pendiente' => $montoTotalPendiente,
                'monto_maximo' => $solicitudesMontoMaximo,
                'monto_minimo' => $solicitudesMontoMinimo,
                'top_solicitudes' => $topSolicitudes,
                'distribucion_montos' => $distribucionMontos
            ];

            return inertia('Estadisticas/Index', [
                'userAuth' => $request->user(),
                'periodoActual' => $periodoActual,
                'fechaInicioQuery' => $fechaInicioQuery,
                'fechaFinQuery' => $fechaFinQuery,
                'solicitudes' => $rawDataFromBackend,
                'rawDataLocalidades' => $rawDataLocalidades,
                'rawDataAreas' => $rawDataAreas,
                'rawDataEstatus' => $rawDataEstatus,
                'estadisticaSeleccionada' => $estadisticaSeleccionada,
                'kpisRecursosPublicos' => $kpisRecursosPublicos,
                'idTipoApoyo' => $tipoApoyoQuery
            ]);
        } else {
            return inertia('Estadisticas/Index', [
                'userAuth' => $request->user(),
                'periodoActual' => $periodoActual,
                'fechaInicioQuery' => $fechaInicioQuery,
                'fechaFinQuery' => $fechaFinQuery,
                'estadisticaSeleccionada' => $estadisticaSeleccionada,
            ]);
        }

        // Retornar una vista de Inertia con los datos necesarios

    }
}
