<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tramite;
use App\Models\CatalogoTramite;
use App\Models\EstatusSolicitud;
use App\Models\EstatusTramite;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TramiteController extends Controller
{
    public function obtenerTramites($fechaInicioQuery, $fechaFinQuery, $estatusQuery)
    {
        $tramitesQuery = Tramite::with([
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
        ])
            ->where('id_tramite', 4)
            ->whereYear('fecha_inicio', now()->year)
            // ->whereBetween('fecha_inicio', [$fechaInicioQuery, $fechaFinQuery])
            ->when(!empty($estatusQuery), function ($query) use ($estatusQuery) {
                return $query->whereIn('id_estatus', (array) $estatusQuery);
            });

        // join('estatus_tramites', 'tramites.id_estatus', '=', 'estatus_tramites.id')
        // ->join('contactos as contactos_solicitantes', 'tramites.id_contacto', '=', 'contactos_solicitantes.id')
        // ->join('personas as personas_solicitantes', 'contactos_solicitantes.id_persona', '=', 'personas_solicitantes.id')
        // ->leftJoin('propiedades', 'tramites.id_propiedad', '=', 'propiedades.id')
        // ->leftJoin('contactos as contactos_propietarios', 'propiedades.id_contacto', '=', 'contactos_propietarios.id')
        // ->leftJoin('personas as personas_propietarios', 'contactos_propietarios.id_persona', '=', 'personas_propietarios.id')
        // ->leftJoin('tramites_tramites', 'tramites.id', '=', 'tramites_tramites.id_solicitud')
        // ->leftJoin('catalogo_tramites', 'tramites_tramites.id_tramite', '=', 'catalogo_tramites.id')
        // ->leftJoin('tramites_razones_sociales', 'tramites.id', '=', 'tramites_razones_sociales.id_solicitud')
        // ->leftJoin('solicitud_referencias', 'tramites.id', '=', 'solicitud_referencias.id_solicitud')

        // ->leftJoin('localidades AS localidades_propiedades', 'propiedades.id_localidad', '=', 'localidades_propiedades.id')
        // ->leftJoin('localidades AS localidades_referencias', 'solicitud_referencias.id_localidad', '=', 'localidades_referencias.id')

        // ->with([
        //     'estatus', 'contacto', 'contacto.persona', 'propiedad', 'propiedad.contacto', 
        //     'propiedad.contacto.persona', 'propiedad.colonia', 'propiedad.localidad', 
        //     'propiedad.tipo', 'destino_obra', 'tramites', 'tramites.tramite', 'razon_social', 'referencia',
        //     'referencia.localidad', 'tramites.tramite.tipoTramite', 'aceptada', 'tramites.tramite.requisitos'
        // ])
        // ->selectRaw('tramites.id, tramites.fecha_ingreso, tramites.id_contacto,
        //                  tramites.folio_digital, tramites.id_propiedad,
        //                  tramites.id_destino_obra, tramites.id_estatus, tramites.folio,
        //                  tramites.fecha_aceptacion,
        //                  COALESCE(CONCAT(personas_propietarios.nombre, personas_propietarios.apellidos), (CONCAT(personas_solicitantes.nombre, personas_solicitantes.apellidos))) AS nombre_solicitante_propietario,
        //                  COALESCE(localidades_propiedades.nombre, localidades_referencias.nombre) AS nombre_localidad_prioritario,
        //                  GROUP_CONCAT(catalogo_tramites.nombre ORDER BY catalogo_tramites.nombre ASC) as tramites_nombres,
        //                  GROUP_CONCAT(catalogo_tramites.id ORDER BY catalogo_tramites.nombre ASC) as id_tramites')
        // ->groupBy('tramites.id', 'tramites.fecha_ingreso', 'tramites.id_contacto',
        //              'tramites.folio_digital', 'tramites.id_propiedad',
        //              'tramites.id_destino_obra', 'tramites.id_estatus', 'tramites.folio',
        //              'tramites.fecha_aceptacion', 'nombre_solicitante_propietario', 'nombre_localidad_prioritario');

        // 2. Lógica Condicional para el Filtro de Fechas
        // 💡 Aplicamos el filtro de fechas solo SI $numQuery está vacío (o no tiene valor significativo).
        //     if ((empty($numQuery) || !is_numeric($numQuery)) && (empty($folioQuery) || !is_numeric($folioQuery))) 
        //     {
        //         if ((empty($fechaAceptacionInicioQuery) || is_null($fechaAceptacionInicioQuery)) && (empty($fechaAceptacionFinQuery) || is_null($fechaAceptacionFinQuery)))
        //         {
        //             $tramitesQuery->whereBetween('tramites.fecha_ingreso', [$fechaIngresoInicioQuery, $fechaIngresoFinQuery]);
        //         }
        //         else
        //         {
        //             $tramitesQuery->whereBetween('tramites.fecha_aceptacion', [$fechaAceptacionInicioQuery, $fechaAceptacionFinQuery]);
        //         }
        //     }

        //     if ($nombreQuery) {
        //         $tramitesQuery->where(function ($query) use ($nombreQuery) {
        //             // Búsqueda por nombre de solicitante
        //             $query->where(function ($q1) use ($nombreQuery) {
        //                 $q1->where('personas_solicitantes.nombre', 'like', '%' . $nombreQuery . '%')
        //                     ->orWhere('personas_solicitantes.apellidos', 'like', '%' . $nombreQuery . '%');
        //             })
        //             // Búsqueda por nombre de propietario
        //             ->orWhere(function ($q2) use ($nombreQuery) {
        //                 $q2->where('personas_propietarios.nombre', 'like', '%' . $nombreQuery . '%')
        //                     ->orWhere('personas_propietarios.apellidos', 'like', '%' . $nombreQuery . '%');
        //             })
        //             // Nueva búsqueda por nombre de razón social
        //             ->orWhere('tramites_razones_sociales.nombre', 'like', '%' . $nombreQuery . '%');
        //         });
        //     }

        //     if ($numQuery) {
        //         // 1. Limpiamos: '0010' -> '0010'
        //         $cleanedNum = preg_replace('/[^0-9]/', '', $numQuery);

        //         if (!empty($cleanedNum)) {

        //             // 2. Normalizamos: '0010' -> '10'
        //             // Utilizamos ltrim() para quitar los ceros a la izquierda.
        //             $normalizedNum = ltrim($cleanedNum, '0');

        //             // Si la cadena queda vacía (ej: si el input era '000'), usamos '0' para prevenir fallos.
        //             if ($normalizedNum === '') {
        //                 $normalizedNum = '0';
        //             }

        //             // 💡 Aplicar la lógica compleja SOLO si la entrada normalizada es corta (menos de 4 dígitos)
        //             if (strlen($normalizedNum) < 4) {

        //                 $tramitesQuery->where(function ($query) use ($normalizedNum) {

        //                     // Opción A: Es el ID exacto (e.g., ID = 10)
        //                     // Buscamos la coincidencia exacta con el número normalizado '10'
        //                     $query->where('tramites.id', $normalizedNum) 

        //                         // Opción B: Es un ID largo que termina en el número (e.g., 1010, 2010)
        //                         ->orWhere(function ($q) use ($normalizedNum) {
        //                             // El patrón es %10
        //                             $searchPattern = '%' . $normalizedNum;

        //                             // Restricción 1: Debe tener 4 o más dígitos
        //                             $q->whereRaw('LENGTH(tramites.id) >= 4')
        //                             // Restricción 2: Debe terminar en el número normalizado ('%10')
        //                             ->whereRaw('CAST(tramites.id AS CHAR) LIKE ?', [$searchPattern]);
        //                         });
        //                 });
        //             }
        //         }
        //     }

        //     if ($folioQuery) {
        //         // 1. Limpiamos: '0010' -> '0010'
        //         $cleanedFolio = preg_replace('/[^0-9Nn]/', '', $folioQuery);

        //         if (!empty($cleanedFolio)) 
        //         {
        //             if ($cleanedFolio == 'N' || $cleanedFolio == 'n')
        //             {
        //                 $tramitesQuery->whereNull('tramites.folio');
        //             }
        //             else 
        //             {
        //                 // 2. Normalizamos: '0010' -> '10'
        //                 // Utilizamos ltrim() para quitar los ceros a la izquierda.
        //                 $normalizedFolio = ltrim($cleanedFolio, '0');

        //                 // Si la cadena queda vacía (ej: si el input era '000'), usamos '0' para prevenir fallos.
        //                 if ($normalizedFolio === '') {
        //                     $normalizedFolio = '0';
        //                 }

        //                 // 💡 Aplicar la lógica compleja SOLO si la entrada normalizada es corta (menos de 4 dígitos)
        //                 if (strlen($normalizedFolio) < 4) {

        //                     $tramitesQuery->where(function ($query) use ($normalizedFolio) {

        //                         // Opción A: Es el ID exacto (e.g., ID = 10)
        //                         // Buscamos la coincidencia exacta con el número normalizado '10'
        //                         $query->where('tramites.folio', $normalizedFolio) 

        //                             // Opción B: Es un ID largo que termina en el número (e.g., 1010, 2010)
        //                             ->orWhere(function ($q) use ($normalizedFolio) {
        //                                 // El patrón es %10
        //                                 $searchPattern = '%' . $normalizedFolio;

        //                                 // Restricción 1: Debe tener 4 o más dígitos
        //                                 $q->whereRaw('LENGTH(tramites.folio) >= 4')
        //                                 // Restricción 2: Debe terminar en el número normalizado ('%10')
        //                                 ->whereRaw('CAST(tramites.folio AS CHAR) LIKE ?', [$searchPattern]);
        //                             });
        //                     });
        //                 }
        //             }
        //         }
        //     }

        //    if ($claveCatastralQuery)
        //    {
        //         $tramitesQuery->where('propiedades.clave_catastral', 'LIKE', $claveCatastralQuery . '%');
        //    }

        //     if (!is_array($tiposTramitesQuery)) {
        //         $tiposTramitesQuery = [$tiposTramitesQuery];
        //     }

        //     if (!empty($tiposTramitesQuery)) 
        //     {
        //         $tramitesQuery->whereIn('catalogo_tramites.id_tipo', $tiposTramitesQuery);
        //     }

        //     $tramitesFiltradosIdsPrev = (clone $tramitesQuery)->pluck('tramites.id');

        //     if (!is_array($tramitesQuery)) {
        //         $tramitesQuery = [$tramitesQuery];
        //     }

        //     if (!empty($tramitesQuery)) 
        //     {
        //         $tramitesQuery->where(function ($query) use ($tramitesQuery) {
        //             // Si seleccionaron trámites con ID (no null)
        //             $tramiteIds = array_filter($tramitesQuery, fn($id) => !is_null($id));

        //             if (!empty($tramiteIds)) {
        //                 $query->whereIn('tramites_tramites.id_tramite', $tramiteIds);
        //             }

        //             // Si seleccionaron la opción "SIN TRÁMITES" (id null)
        //             if (in_array(null, $tramitesQuery, true)) {
        //                 $query->orWhereNull('tramites_tramites.id_tramite');
        //             }
        //         });
        //     }

        //     // 1. Aseguramos que $localidadesQueryFiltrados sea un array.
        //     if (!is_array($localidadesQueryFiltrados)) {
        //         $localidadesQueryFiltrados = [$localidadesQueryFiltrados];
        //     }

        //     if (!empty($localidadesQueryFiltrados)) 
        //     {
        //         // 1. Aseguramos que $localidadesQueryFiltrados sea un array.
        //         if (!is_array($localidadesQueryFiltrados)) {
        //             $localidadesQueryFiltrados = [$localidadesQueryFiltrados];
        //         }

        //         if (!empty($localidadesQueryFiltrados)) 
        //         {
        //             // Normalización: 
        //             // Separamos los IDs reales (incluye ID 0) de la opción "N/A" (la cadena 'null').
        //             $localidadIds = [];
        //             $incluirNulos = false;

        //             foreach ($localidadesQueryFiltrados as $id) {
        //                 // La cadena 'null' se convierte en el indicador para el filtro IS NULL.
        //                 if ($id === '-99' || is_null($id)) {
        //                     $incluirNulos = true;
        //                 } elseif (is_numeric($id) || $id !== '') {
        //                     // Incluye IDs numéricos (cadenas o enteros), como '1' o 0.
        //                     $localidadIds[] = $id;
        //                 }
        //             }

        //             // Si no hay nada que filtrar, salimos.
        //             if (empty($localidadIds) && !$incluirNulos) {
        //                 return; 
        //             }

        //             $tramitesQuery->where(function ($query) use ($localidadIds, $incluirNulos) {

        //                 $hasExistingCondition = false;

        //                 // A) FILTRAR POR IDs REALES (Priorización: Propiedad > Referencia)
        //                 if (!empty($localidadIds)) {
        //                     $query->where(function ($q) use ($localidadIds) {
        //                         // 1. Coincidencia en la Propiedad (Prioridad)
        //                         $q->whereIn('propiedades.id_localidad', $localidadIds) 
        //                         // O (OR)
        //                         // 2. Coincidencia en la Referencia, SÓLO si la Propiedad NO tiene ID de Localidad.
        //                         ->orWhere(function($q_ref) use ($localidadIds) {
        //                             $q_ref->whereNull('propiedades.id_localidad') 
        //                                     ->whereIn('solicitud_referencias.id_localidad', $localidadIds);
        //                         });
        //                     });
        //                     $hasExistingCondition = true;
        //                 }

        //                 // B) FILTRAR POR NULL ("N/A")
        //                 if ($incluirNulos) {
        //                     // Si solo se seleccionó 'null', usamos 'where'. Si se seleccionó con IDs, usamos 'orWhere'.
        //                     $method = $hasExistingCondition ? 'orWhere' : 'where';

        //                     $query->$method(function ($q_null) {
        //                         // Una solicitud es N/A si NO tiene id_localidad en Propiedad Y NO tiene id_localidad en Referencia.
        //                         $q_null->whereNull('propiedades.id_localidad')
        //                             ->whereNull('solicitud_referencias.id_localidad');
        //                     });
        //                 }
        //             });
        //         }
        //     }

        //     if ($estatusQuery && !is_array($estatusQuery)) {
        //         $estatusQuery = [$estatusQuery];
        //     }

        //     if (!empty($estatusQuery)) 
        //     {
        //         $tramitesQuery->whereIn('id_estatus', $estatusQuery);
        //     }


        //     if ($sortColumn == "id_propietario")
        //     {
        //         $tramitesQuery->orderBy('nombre_solicitante_propietario', $sortDirection);
        //     }
        //     else if ($sortColumn == 'id_tramite')
        //     {
        //         $tramitesQuery->orderBy('tramites_nombres', $sortDirection);
        //     }
        //     else if ($sortColumn == 'id_estatus') {
        //         $tramitesQuery->orderBy('estatus_tramites.nombre', $sortDirection)
        //                         ->orderBy('tramites.fecha_ingreso', $sortDirection);
        //     }
        //     else if ($sortColumn == 'fecha_ingreso') {
        //         $tramitesQuery->orderBy('tramites.fecha_ingreso', $sortDirection)
        //                         ->orderBy('tramites.id', $sortDirection);
        //     }
        //     else if ($sortColumn == 'clave_catastral') {
        //         $tramitesQuery->orderBy('propiedades.clave_catastral', $sortDirection);
        //     } 
        //     else if ($sortColumn == 'id_localidad') 
        //     {
        //         $tramitesQuery->orderBy('nombre_localidad_prioritario', $sortDirection);
        //     } 
        //     else if ($sortColumn == 'folio') 
        //     {
        //         $tramitesQuery->orderByRaw('tramites.folio IS NULL');
        //         $tramitesQuery->orderBy('tramites.folio', $sortDirection);
        //     } 
        //     else
        //     {
        //         $tramitesQuery->orderBy($sortColumn, $sortDirection);
        //     } 

        return [
            'query' => $tramitesQuery,
            // 'ids_previos' => $tramitesFiltradosIdsPrev
        ];
    }

    public function obtenerResumenTramites(array $params): array
    {
        extract($params); // Extrae variables como $filtroChkSolicitudes, $nombreQuery, etc.

        $tramites = collect();
        $estatusSolicitud = collect();
        $localidadesCount = collect();
        $tramitesSinTramitesCount = 0;

        $usarFiltro = !empty($tiposTramitesQuery);
        $usarNum = !empty($numQuery);
        $usarFolio = !empty($folioQuery);
        $usarNombre = !empty($nombreQuery);
        $usarClaveCatastral = !empty($claveCatastralQuery);
        $usarLocalidad = !empty($localidadesQueryFiltrados);
        $usarAmbosFiltros = !empty($tramitesQuery) && !empty($estatusQuery);
        $usarFechas = !is_null($fechaIngresoInicioQuery) && !is_null($fechaIngresoFinQuery);
        $usarFechasAceptacion = !is_null($fechaAceptacionInicioQuery) && !is_null($fechaAceptacionFinQuery);

        $getTramites = fn($prev = false, $filtrar = false) =>
        $prev
            ? $this->obtenerTramites(false, $tramitesFiltradosIdsPrev, $tiposTramitesQuery, $filtrar, $localidadesQueryFiltrados)
            : $this->obtenerTramites(false, $tramitesFiltradosIds, $tiposTramitesQuery, $filtrar, $localidadesQueryFiltrados);

        $getEstatus = fn($prev = false) =>
        $prev
            ? $this->obtenerEstatus($tramitesFiltradosIdsPrev)
            : $this->obtenerEstatus($tramitesFiltradosIds);

        $getSinTramites = fn($prev = false) =>
        $prev
            ? $this->contarSinTramites($tramitesFiltradosIdsPrev)
            : $this->contarSinTramites($tramitesFiltradosIds);

        $getLocalidades = fn($prev = false) =>
        $prev
            ? $this->contarLocalidades($tramitesFiltradosIdsPrev)
            : $this->contarLocalidades($tramitesFiltradosIds);

        switch ($filtroChkSolicitudes) {
            case 0:
                if ($usarNum || $usarNombre || $usarFiltro || $usarFechas || $usarFolio || $usarClaveCatastral || $usarFechasAceptacion) {
                    if ($usarLocalidad) {
                        $tramites = $getTramites(false, $usarFiltro);
                        $estatusSolicitud = $getEstatus(false);
                        $tramitesSinTramitesCount = $getSinTramites(false);
                        $localidadesQuery = $getLocalidades(true);
                    } else {
                        $tramites = $getTramites(true, $usarFiltro);
                        $estatusSolicitud = $getEstatus(true);
                        $tramitesSinTramitesCount = $getSinTramites(true);
                        $localidadesQuery = $getLocalidades(false);
                    }
                } else if ($usarLocalidad) {
                    $tramites = $getTramites(false, $usarFiltro);
                    $estatusSolicitud = $getEstatus(false);
                    $tramitesSinTramitesCount = $getSinTramites(true);
                    $localidadesQuery = $getLocalidades(true);
                } else {
                    $tramites = CatalogoTramite::where('activo', 1)
                        ->orderBy('nombre')
                        ->withCount('tramitesTramites')
                        ->get()
                        ->map(fn($t) => [
                            'id' => $t->id,
                            'nombre' => $t->nombre,
                            'count' => $t->tramites_tramites_count,
                        ]);

                    $estatusSolicitud = empty($tramitesQuery)
                        ? EstatusSolicitud::where('activo', true)
                        ->withCount('tramites')
                        ->get()
                        ->map(fn($e) => [
                            'id' => $e->id,
                            'nombre' => $e->nombre,
                            'color' => $e->color,
                            'count' => $e->tramites_count,
                        ])
                        : $getEstatus(false);

                    $tramitesSinTramitesCount = $getSinTramites(false);

                    $localidadesQuery = $getLocalidades(false);
                }
                break;

            case 1:
                $tramites = $getTramites(true, $usarFiltro);
                $estatusSolicitud = $getEstatus(false);
                $tramitesSinTramitesCount = $getSinTramites(true);
                $localidadesQuery = $getLocalidades(false);

                if ($usarAmbosFiltros) {
                    $tramites = $getTramites(true, $usarFiltro);
                    $tramitesSinTramitesCount = $getSinTramites(false);
                }
                break;

            case 2:
                $estatusSolicitud = $getEstatus(true);
                $tramites = $getTramites(false, $usarFiltro);
                $tramitesSinTramitesCount = $getSinTramites(false);
                $localidadesQuery = $getLocalidades(false);

                if ($usarAmbosFiltros) {
                    $estatusSolicitud = $getEstatus(true);
                    $tramitesSinTramitesCount = $getSinTramites(false);
                }
                break;
        }

        if ($tramitesSinTramitesCount > 0) {
            $tramites->push([
                'id' => null,
                'nombre' => 'SIN TRÁMITES',
                'count' => $tramitesSinTramitesCount,
            ]);
        }

        return compact('tramites', 'estatusSolicitud', 'tramitesSinTramitesCount', 'localidadesQuery');
    }

    private function prepararVistaTramites(array $filtros): array
    {
        $tramitesQuery = $this->obtenerTramites(
            $filtros['fechaInicioQuery'],
            $filtros['fechaFinQuery'],
            $filtros['estatusQuery'],
        );


        $tramites = $tramitesQuery['query']->paginate(8);

        $estatus = EstatusTramite::all();

        return [
            'userAuth' => Auth::user(),
            'tramites' => $tramites,
            'todosEstatus' => $estatus,
        ];
    }

    public function index(Request $request)
    {
        $fechaInicio = $request->input('fechaInicioQuery');
        $fechaFin = $request->input('fechaFinQuery');

        // $query = Tramite::query();

        // 3. Aplicamos los filtros solo si existen y son válidos
        if ($fechaInicio && $fechaFin) {
            // Convertimos de DD-MM-YYYY a YYYY-MM-DD para la base de datos
            $fechaInicioQuery = Carbon::parse(trim($fechaInicio))->startOfDay();
            $fechaFinQuery    = Carbon::parse(trim($fechaFin))->endOfDay();
        } else {
            $fechaFinQuery = Carbon::now()->endOfDay(); // Hoy a las 23:59:59
            $fechaInicioQuery = Carbon::now()->subMonth()->startOfDay(); // Hace 1 mes a las 00:00:00
        }

        $estatusQuery = $request->input('estatusQuery');

        $page = $request->input('page', 1);

        $filtros = [
            'fechaInicioQuery' => $fechaInicioQuery,
            'fechaFinQuery' => $fechaFinQuery,
            'estatusQuery' => $estatusQuery,
            'page' => $page,
        ];

        $props = $this->prepararVistaTramites($filtros);

        $props['filters'] = $filtros;

        return Inertia::render('Tramites/Index', $props);
    }

    public function edit(Request $request, $tipo_tramite)
    {
        $tramite = Tramite::findOrFail($request->input('id_tramite'));
        $tramite->load([
            'tipoTramite',
            'solicitud',
            'solicitud.contacto.persona',
            'solicitud.razon_social',
            'propiedad',
            'propiedad.tipo',
            'propiedad.contacto',
            'propiedad.contacto.persona',
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
        ]);


        if ($tramite->tipoTramite->slug !== $tipo_tramite) {
            abort(404, 'El tipo de trámite no coincide con el expediente solicitado.');
        }

        $nombreFormato = Str::studly($tipo_tramite);

        $estatus = EstatusTramite::all();

        return Inertia::render("Tramites/Edit/{$nombreFormato}", [
            'tramite' => $tramite,
            'documentoData' => $tramite->documento_generado,
            'todosEstatus' => $estatus,
            'desdePropiedad' => $request->boolean('desde_propiedad'),
            'userAuth' => Auth::user(),
        ]);
    }
}
