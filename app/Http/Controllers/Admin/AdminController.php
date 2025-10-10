<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth; // Asegúrate de importar Auth
use App\Models\Solicitud; // Para el panel de últimas solicitudes
use App\Models\CatalogoTramite; // Para el panel de últimas solicitudes
use App\Models\DashboardPanelConfig; // Para la configuración de los paneles
use Illuminate\Support\Facades\DB; // Para consultas de agrupación (ej. por mes)
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index()
    {
        // 1. Obtener la configuración de los paneles desde la base de datos
        // Carga la configuración global. Si quieres por usuario, ajusta la consulta.
        // Obtener los roles del usuario autenticado como un array
        $userRoles = auth()->user()->getRoleNames()->toArray();
        $userRole = $userRoles[0] ?? null;

        // dd($userRoles); // Para depurar y ver los roles del usuario autenticado

        // Construir la consulta
        $panelConfigs = DashboardPanelConfig::whereNull('user_id')
            ->where(function ($query) use ($userRoles) {
                $query->whereNull('roles'); // Muestra los paneles que no tienen roles asignados (globales)

                // Itera sobre los roles del usuario para buscar una coincidencia en el array JSON
                foreach ($userRoles as $role) {
                    $query->orWhereJsonContains('roles', $role);
                }
            })
            ->orderBy('order')
            ->get();

        // 2. Preparar un array para almacenar todos los datos que los paneles necesitan
        $dataForPanels = [];
        $vuePanelConfig = []; // Configuración que se pasará a Vue para renderizar los componentes

        // 3. Iterar sobre la configuración para cargar los datos específicos de cada panel
        foreach ($panelConfigs as $config) {
            $panelData = null; // Inicializa los datos para el panel actual
            $dataKey = $config->panel_type; // La clave bajo la cual se guardarán los datos en $dataForPanels

            $limit = in_array('AUXILIAR', $userRoles) ? 8 : 3;

            switch ($config->panel_type) {
                case 'latest_solicitudes':
                   $panelData = Solicitud::with(['contacto', 'propiedad', 'estatus', 'contacto.persona', 'propiedad.contacto.persona', 'propiedad.colonia', 'propiedad.localidad', 'referencia', 'referencia.localidad'])
                        ->orderByDesc('fecha_ingreso')  // 🔹 Primero por fecha más reciente
                        ->orderByDesc('id')             // 🔸 Luego por ID más alto
                        ->take($limit)
                        ->get();

                    break;

                case 'solicitudes_by_month':
                    // Lógica para solicitudes por mes
                    $panelData = Solicitud::select(
                                        DB::raw('MONTH(fecha_ingreso) as month'),
                                        DB::raw('YEAR(fecha_ingreso) as year'),
                                        DB::raw('count(*) as total')
                                    )
                                    ->groupBy('year', 'month')
                                    ->orderBy('year', 'desc')
                                    ->orderBy('month', 'desc')
                                    ->take(4) // Últimos 6 meses, por ejemplo
                                    ->get();

                    break;

                case 'tramites_by_year':
                    // Asumiendo que "trámites" también se guardan en la tabla 'solicitudes'
                    // o que tienes otra tabla para trámites. Si es otra tabla,
                    // ajusta el modelo y la consulta. Aquí, usaré 'solicitudes' como ejemplo.

                    $panelData = CatalogoTramite::select('catalogo_tramites.nombre_abreviado', DB::raw('COUNT(*) as total'))
                        ->join('solicitudes_tramites', 'catalogo_tramites.id', '=', 'solicitudes_tramites.id_tramite')
                        ->join('solicitudes', 'solicitudes.id', '=', 'solicitudes_tramites.id_solicitud')
                        ->whereYear('solicitudes.fecha_ingreso', Carbon::now()->year) // 🎯 Año dinámico
                        ->groupBy('catalogo_tramites.id', 'catalogo_tramites.nombre_abreviado')
                        ->orderByDesc('total')
                        ->limit(3)
                        ->get();

                    break;

                case 'solicitudes_by_location':
                    $panelData = Solicitud::select(
                        'localidades.nombre as localidad_nombre',
                        DB::raw('count(*) as total')
                    )->whereYear('fecha_ingreso', now()->year)
                    ->join('propiedades', 'solicitudes.id_propiedad', '=', 'propiedades.id')
                    ->join('localidades', 'propiedades.id_localidad', '=', 'localidades.id')
                    ->groupBy('localidades.nombre')
                    ->orderByDesc('total')
                    ->take(6)
                    ->get();


                    break;
                case 'people_solicitudes':
                    // Lógica para solicitudes por mes
                   $panelData = Solicitud::whereYear('fecha_ingreso', now()->year)->count();

                    break;

                case 'solicitudes_by_property_type':
                    $panelData = Solicitud::select(
                        DB::raw('COALESCE(T1.nombre, T2.nombre, "N/A") as tipo_propiedad_nombre'),
                        DB::raw('count(*) as total')
                    )->whereYear('fecha_ingreso', now()->year)
                    ->leftJoin('propiedades', 'solicitudes.id_propiedad', '=', 'propiedades.id')
                    ->leftJoin('tipos_propiedades as T1', 'propiedades.id_tipo', '=', 'T1.id') // Alias T1 para propiedades
                    ->leftJoin('solicitud_referencias', 'solicitudes.id', '=', 'solicitud_referencias.id_solicitud')
                    ->leftJoin('tipos_propiedades as T2', 'solicitud_referencias.id_tipo_propiedad', '=', 'T2.id') // Alias T2 para solicitud_referencias
                    ->groupBy('tipo_propiedad_nombre')
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
        }
 
        // 4. Renderizar el componente Inertia, pasando todas las props necesarias
        return Inertia::render('Admin/Dashboard', [ // ¡Tu componente es 'Admin/Dashboard'!
            'userAuth' => Auth::user(), // Tu prop existente
            'panelConfig' => $vuePanelConfig, // La configuración de los paneles
            'panelData' => $dataForPanels,   // Los datos para cada panel
            'userRole' => $userRole, // El rol del usuario autenticado
        ]);
    }
}
