<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\SettingsHelper;
use App\Models\Periodo;
use App\Models\Solicitud; // Para el panel de últimas solicitudes
use App\Models\CatalogoTramite; // Para el panel de últimas solicitudes
use Illuminate\Support\Facades\DB; // Para consultas de agrupación (ej. por mes)
use Carbon\Carbon;

class EstadisticasController extends Controller
{
    //
    public function index(Request $request)  
    {
        // Aquí puedes manejar la lógica para las estadísticas
        // Por ejemplo, podrías obtener datos de la base de datos y pasarlos a una vista

        // Obtener el ID del periodo actual
        $periodoId = SettingsHelper::get('periodo_actual');

        // Usar el ID para buscar el objeto completo
        $periodoActual = Periodo::find($periodoId);

        $tipoGrafico = $request->input('tipoGrafico'); // Valor por defecto 'bar'
        $tipoEstadistica = $request->input('tipoEstadistica'); // Valor por defecto 'solicitudes_por_estado'
        $fechaInicioQuery = $request->input('fechaInicioQuery'); // Valor por defecto null
        $fechaFinQuery = $request->input('fechaFinQuery'); // Valor por defecto
        $vieneDeDashboard = $request->input('vieneDeDashboard');

        // dd($request->all());

        $solicitudes = null;
        $agruparPorDia = true;

        $totalSolicitudes = null;
        $tramitesMasSolicitados = null;
        $totalTramitesMasSolicitados = null;
        $tiposPropiedad = null;
        $localidadesMasSolicitadas = null;
        $solicitudesQuery = Solicitud::whereBetween('fecha_ingreso', [$fechaInicioQuery, $fechaFinQuery]);

        if ($tipoEstadistica == '1')   //Líneas
        {
            // Convertir las fechas a objetos Carbon para una manipulación más fácil
            $fechaInicioCarbon = Carbon::parse($fechaInicioQuery);
            $fechaFinCarbon = Carbon::parse($fechaFinQuery);

            // Iniciar la consulta base
            $totalSolicitudes = $solicitudesQuery->count();

            // Lógica condicional para agrupar por mes o por día
            if ($fechaInicioCarbon->year === $fechaFinCarbon->year && $fechaInicioCarbon->month === $fechaFinCarbon->month) {
                // El rango es dentro del mismo mes y año, agrupar por día
                $solicitudes = $solicitudesQuery->select(
                    DB::raw('DAY(fecha_ingreso) as day'),
                    DB::raw('MONTH(fecha_ingreso) as month'),
                    DB::raw('YEAR(fecha_ingreso) as year'),
                    DB::raw('count(*) as total')
                )
                ->groupBy('year', 'month', 'day')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->orderBy('day', 'desc')
                ->get();
            } 
            else 
            {
                // El rango abarca más de un mes, agrupar por mes
                $solicitudes = $solicitudesQuery->select(
                    DB::raw('MONTH(fecha_ingreso) as month'),
                    DB::raw('YEAR(fecha_ingreso) as year'),
                    DB::raw('count(*) as total')
                )
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

                $agruparPorDia = false;  
            }
        }
        else if ($tipoEstadistica == '2')   //Barras
        {
           $solicitudIds = $solicitudesQuery->pluck('id');
           $elementosRanking = (int) $request->input('elementosRanking', 3);

           $tramitesMasSolicitados = CatalogoTramite::select('catalogo_tramites.nombre_abreviado', DB::raw('COUNT(*) as total'))
                ->join('solicitudes_tramites', 'catalogo_tramites.id', '=', 'solicitudes_tramites.id_tramite')
                ->whereIn('solicitudes_tramites.id_solicitud', $solicitudIds)
                ->groupBy('catalogo_tramites.id', 'catalogo_tramites.nombre_abreviado')
                ->orderByDesc('total')
                ->limit($elementosRanking)
                ->get();

            $totalTramitesMasSolicitados = CatalogoTramite::join('solicitudes_tramites', 'catalogo_tramites.id', '=', 'solicitudes_tramites.id_tramite')
            ->whereIn('solicitudes_tramites.id_solicitud', $solicitudIds)
            ->count();

        }
        else if ($tipoEstadistica == '3')   //Dona
        {
            $totalSolicitudes = $solicitudesQuery->count();

            $tiposPropiedad = $solicitudesQuery->select(
                DB::raw('COALESCE(T1.nombre, T2.nombre, "N/A") as tipo_propiedad_nombre'),
                DB::raw('count(*) as total')
            )
            ->leftJoin('propiedades', 'solicitudes.id_propiedad', '=', 'propiedades.id')
            ->leftJoin('tipos_propiedades as T1', 'propiedades.id_tipo', '=', 'T1.id') // Alias T1 para propiedades
            ->leftJoin('solicitud_referencias', 'solicitudes.id', '=', 'solicitud_referencias.id_solicitud')
            ->leftJoin('tipos_propiedades as T2', 'solicitud_referencias.id_tipo_propiedad', '=', 'T2.id') // Alias T2 para solicitud_referencias
            ->groupBy('tipo_propiedad_nombre')
            ->orderByDesc('total')
            ->get();
        }
        else if ($tipoEstadistica == '4')   //radial
        {
            $totalSolicitudes = $solicitudesQuery->count();
            $elementosRanking = (int) $request->input('elementosRanking', 3);

            $localidadesMasSolicitadas = $solicitudesQuery->select(
                        // DB::raw('IFNULL(localidades.nombre, "N/A") as localidad_nombre'),
                        DB::raw('COALESCE(T1.nombre, T2.nombre, "N/A") as localidad_nombre'),
                        DB::raw('count(*) as total')
                    )
                    ->leftJoin('propiedades', 'solicitudes.id_propiedad', '=', 'propiedades.id')
                    ->leftJoin('localidades as T1', 'propiedades.id_localidad', '=', 'T1.id')
                    ->leftJoin('solicitud_referencias', 'solicitudes.id', '=', 'solicitud_referencias.id_solicitud')
                    ->leftJoin('localidades as T2', 'solicitud_referencias.id_localidad', '=', 'T2.id') // Alias T2 para solicitud_referencias
                    ->groupBy('localidad_nombre')
                    ->orderByDesc('total')
                    ->take($elementosRanking)
                    ->get();
        }

        // Retornar una vista de Inertia con los datos necesarios
        return inertia('Estadisticas/Index', [
            'userAuth' => $request->user(),
            'periodoActual' => $periodoActual,
            'tipoGrafico' => $tipoGrafico,
            'tipoEstadistica' => $tipoEstadistica,
            'fechaInicioQuery' => $fechaInicioQuery,
            'fechaFinQuery' => $fechaFinQuery,
            'solicitudes' => $solicitudes,
            'totalSolicitudes' => $totalSolicitudes,
            'agruparPorDia' => $agruparPorDia,
            'tramitesMasSolicitados' => $tramitesMasSolicitados,
            'totalTramitesMasSolicitados' => $totalTramitesMasSolicitados,
            'tiposPropiedad' => $tiposPropiedad,
            'localidadesMasSolicitadas' => $localidadesMasSolicitadas,
            'vieneDeDashboard' => $vieneDeDashboard
        ]);
    }
}
