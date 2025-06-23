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
use App\Models\TipoTramite;
use App\Models\Contacto;
use App\Models\Propiedad;
use App\Models\SolicitudTramite;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SolicitudController extends Controller
{
    public $activeTab;

    public function getPersona(Request $request, $curp)
    {
        // if (!auth()->check()) {
        //     return response()->json(['error' => 'No autenticado'], 401);
        // }

        if ($request->query('tipo') === 'solicitante')
        {
            $persona = Persona::with('solicitante', 'solicitante.persona')
            ->where('curp', $curp)
            ->where('activa', 1)->first();
        }
        elseif ($request->query('tipo') === 'propietario')
        {
            $persona = Persona::with('propietario', 'propietario.persona')
            ->where('curp', $curp)
            ->where('activa', 1)->first();
        }
        else
        {
            $persona = Persona::with('solicitante', 'solicitante.colonia', 'solicitante.localidad')
            ->where('curp', $curp)
            ->where('activa', 1)->first();
        }  
        

        return response()->json([
            'persona' => $persona
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
        $propiedad->load('colonia', 'localidad', 'tipo', 'solicitudes', 'contacto', 'contacto.persona');

        return response()->json([
            'propiedad' => $propiedad,
        ]);
    }

    public function getColonias(Request $request)
    {
        $nombre = $request->query('nombre');
        $colonias = null;

        if (strlen($nombre) > 0)
        { 
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

        if (strlen($nombre) > 0)
        { 
            $localidades = Localidad::where('nombre', 'like', '%' . $nombre . '%')
            ->limit(6)
            ->get();
        }

        return response()->json([
            'localidades' => $localidades,
        ]);
    }

    public function index(Request $request)  
    {
        $sortColumn = $request->input('sortColumn', 'id'); // Columna de ordenación
        $sortDirection = $request->input('sortDirection', 'asc'); // Dirección de ordenación

        $nombreQuery = $request->input('nombreQuery');
        $fechaInicioQuery = $request->input('fechaInicioQuery');
        $fechaFinQuery = $request->input('fechaFinQuery');
        $tiposTramitesQuery = $request->input('tiposTramitesQuery');
        $tramitesQuery = $request->input('tramitesQuery');
        $estatusQuery = $request->input('estatusQuery');
        $filtroChkSolicitudes = $request->input('filtroChkSolicitudes', 0);


        if (empty($tramitesQuery) && empty($estatusQuery))
        {
            $filtroChkSolicitudes = 0;
        }
        
        if ($tramitesQuery && empty($estatusQuery))
        {
            $filtroChkSolicitudes = 1;
        }

        if (empty($tramitesQuery) && $estatusQuery)
        {
            $filtroChkSolicitudes = 2;
        }

        $solicitudesQuery = Solicitud::
              join('estatus_solicitudes', 'solicitudes.id_estatus', '=', 'estatus_solicitudes.id')
            ->join('contactos as contactos_solicitantes', 'solicitudes.id_contacto', '=', 'contactos_solicitantes.id')
            ->join('personas as personas_solicitantes', 'contactos_solicitantes.id_persona', '=', 'personas_solicitantes.id')
            ->join('propiedades', 'solicitudes.id_propiedad', '=', 'propiedades.id')
            ->join('contactos as contactos_propietarios', 'propiedades.id_contacto', '=', 'contactos_propietarios.id')
            ->join('personas as personas_propietarios', 'contactos_propietarios.id_persona', '=', 'personas_propietarios.id')
            ->leftJoin('solicitudes_tramites', 'solicitudes.id', '=', 'solicitudes_tramites.id_solicitud')
            ->leftJoin('catalogo_tramites', 'solicitudes_tramites.id_tramite', '=', 'catalogo_tramites.id')
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
                'tramites.tramite'
            ])
            ->whereBetween('solicitudes.fecha_ingreso', [$fechaInicioQuery, $fechaFinQuery])
            ->selectRaw('solicitudes.id, solicitudes.fecha_ingreso, solicitudes.id_contacto, 
                         solicitudes.folio_digital, solicitudes.id_propiedad, 
                         solicitudes.id_destino_obra, solicitudes.id_estatus, 
                         GROUP_CONCAT(catalogo_tramites.nombre ORDER BY catalogo_tramites.nombre ASC) as tramites_nombres')
            ->groupBy('solicitudes.id', 'solicitudes.fecha_ingreso', 'solicitudes.id_contacto', 
                      'solicitudes.folio_digital', 'solicitudes.id_propiedad', 
                      'solicitudes.id_destino_obra', 'solicitudes.id_estatus');    


        if ($nombreQuery)
        {
            $solicitudesQuery->where(function ($query) use ($nombreQuery) {
                $query->where(function ($query) use ($nombreQuery) {
                    $query->where(function ($q1) use ($nombreQuery) {
                        $q1->where('personas_solicitantes.nombre', 'like', '%' . $nombreQuery . '%')
                        ->orWhere('personas_solicitantes.apellidos', 'like', '%' . $nombreQuery . '%');
                    })->orWhere(function ($q2) use ($nombreQuery) {
                        $q2->where('personas_propietarios.nombre', 'like', '%' . $nombreQuery . '%')
                        ->orWhere('personas_propietarios.apellidos', 'like', '%' . $nombreQuery . '%');
                    });
                });
            });
        }

        if (!empty($tiposTramitesQuery)) 
        {
            $solicitudesQuery->whereIn('catalogo_tramites.id_tipo', $tiposTramitesQuery);
        }

        $solicitudesFiltradasIdsPrev = (clone $solicitudesQuery)->pluck('solicitudes.id');

        if (!empty($tramitesQuery)) 
        {
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

        if (!empty($estatusQuery)) 
        {
            $solicitudesQuery->whereIn('id_estatus', $estatusQuery);
        }

        if ($sortColumn == "id_solicitante")
        {
            $solicitudesQuery->orderBy('personas_solicitantes.nombre', $sortDirection);
        }
        else if ($sortColumn == 'id_tramite')
        {
            $solicitudesQuery->orderBy('tramites_nombres', $sortDirection);
        }
        else if ($sortColumn == 'id_estatus') {
            $solicitudesQuery->orderBy('estatus_solicitudes.nombre', $sortDirection)
                            ->orderBy('solicitudes.fecha_ingreso', $sortDirection);
        } 
        else 
        {
            $solicitudesQuery->orderBy($sortColumn, $sortDirection);
        }

        $solicitudes = $solicitudesQuery->paginate(10);

        $tiposPropiedades = TipoPropiedad::orderBy('nombre', 'asc')->get();
        $destinosObras = DestinoObra::orderBy('nombre', 'asc')->get();
        $tiposTramites = TipoTramite::with('tramites')->where('activo', true)->get();
        $localidades = Localidad::get();

        $solicitudesFiltradasIds = (clone $solicitudesQuery)->pluck('solicitudes.id');

        // Función reutilizable: obtener conteo de trámites
        $obtenerTramites = function ($filtrarPorTipo = false) use ($solicitudesFiltradasIds, $tiposTramitesQuery, $estatusQuery) {
            $query = CatalogoTramite::where('activo', 1)->orderBy('nombre');

            if ($filtrarPorTipo && !empty($tiposTramitesQuery)) 
            {
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
        };

        $obtenerTramitesPrev = function ($filtrarPorTipo = false) use ($solicitudesFiltradasIdsPrev, $tiposTramitesQuery, $estatusQuery) {
            $query = CatalogoTramite::where('activo', 1)->orderBy('nombre');

            if ($filtrarPorTipo && !empty($tiposTramitesQuery)) 
            {
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
        };

        // Función reutilizable: obtener conteo de estatus
        $obtenerEstatus = function () use ($solicitudesFiltradasIds) {
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
        };

        $obtenerEstatusPrev = function () use ($solicitudesFiltradasIdsPrev) {
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
        };

        // Función reutilizable: contar solicitudes sin trámites
        $contarSinTramites = function () use ($solicitudesFiltradasIds) {
            return DB::table('solicitudes')
                ->whereIn('id', $solicitudesFiltradasIds)
                ->whereNotIn('id', function ($query) {
                    $query->select('id_solicitud')->from('solicitudes_tramites');
                })
                ->count();
        };

        $contarSinTramitesPrev = function () use ($solicitudesFiltradasIdsPrev) {
            return DB::table('solicitudes')
                ->whereIn('id', $solicitudesFiltradasIdsPrev)
                ->whereNotIn('id', function ($query) {
                    $query->select('id_solicitud')->from('solicitudes_tramites');
                })
                ->count();
        };

        if (empty($tiposTramitesQuery)) {
            if ($nombreQuery) 
            {
                if ($filtroChkSolicitudes == 0)
                { 
                    $tramites = $obtenerTramitesPrev();
                    $estatusSolicitud = $obtenerEstatusPrev();
                    $solicitudesSinTramitesCount = $contarSinTramitesPrev();
                }
                else if ($filtroChkSolicitudes == 1)
                {
                    $tramites = $obtenerTramitesPrev();
                    $estatusSolicitud = $obtenerEstatus();
                    $solicitudesSinTramitesCount = $contarSinTramitesPrev();
                    if ($tramitesQuery && $estatusQuery)
                    {
                        $tramites = $obtenerTramitesPrev();
                        $solicitudesSinTramitesCount = $contarSinTramites();
                    }
                }
                else if ($filtroChkSolicitudes == 2)
                {
                    $estatusSolicitud = $obtenerEstatusPrev();
                    $tramites = $obtenerTramites();
                    $solicitudesSinTramitesCount = $contarSinTramites();
                    if ($tramitesQuery && $estatusQuery)
                    {
                         $estatusSolicitud = $obtenerEstatusPrev();
                         $solicitudesSinTramitesCount = $contarSinTramites();
                    }
                }
            } 
            else 
            {
                if ($filtroChkSolicitudes == 0)
                { 
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
                        : $obtenerEstatus();

                    $solicitudesSinTramitesCount = $contarSinTramites();
                }
                else if ($filtroChkSolicitudes == 1)
                {
                    $tramites = $obtenerTramitesPrev();
                    $estatusSolicitud = $obtenerEstatus();
                    $solicitudesSinTramitesCount = $contarSinTramitesPrev();
                    if ($tramitesQuery && $estatusQuery)
                    {
                        $tramites = $obtenerTramitesPrev();
                        $solicitudesSinTramitesCount = $contarSinTramites();
                    }
                }
                else if ($filtroChkSolicitudes == 2)
                {
                    $estatusSolicitud = $obtenerEstatusPrev();
                    $tramites = $obtenerTramites();
                    $solicitudesSinTramitesCount = $contarSinTramites();
                    if ($tramitesQuery && $estatusQuery)
                    {
                         $estatusSolicitud = $obtenerEstatusPrev();
                         $solicitudesSinTramitesCount = $contarSinTramites();
                    }
                }   
                    
            }
        } 
        else 
        {
            if ($filtroChkSolicitudes == 0)
            { 
                $tramites = $obtenerTramitesPrev();
                $estatusSolicitud = $obtenerEstatusPrev();
                $solicitudesSinTramitesCount = $contarSinTramitesPrev();
            }
            else if ($filtroChkSolicitudes == 1)
            {
                $tramites = $obtenerTramitesPrev();
                $estatusSolicitud = $obtenerEstatus();
                $solicitudesSinTramitesCount = $contarSinTramitesPrev();
                if ($tramitesQuery && $estatusQuery)
                {
                    $tramites = $obtenerTramites(true);
                    $solicitudesSinTramitesCount = $contarSinTramites();
                }
            }
            else if ($filtroChkSolicitudes == 2)
            {
                $estatusSolicitud = $obtenerEstatusPrev();
                $tramites = $obtenerTramites(true);
                $solicitudesSinTramitesCount = $contarSinTramites();
                if ($tramitesQuery && $estatusQuery)
                {
                    $estatusSolicitud = $obtenerEstatusPrev();
                    $solicitudesSinTramitesCount = $contarSinTramites();
                }
            }
        }

        if ($solicitudesSinTramitesCount > 0)
        { 

            $tramites->push([
                'id' => null,
                'nombre' => 'SIN TRÁMITES',
                'count' => $solicitudesSinTramitesCount,
            ]);
        }

        return Inertia::render('Solicitudes/Index', [
            'userAuth' => Auth::user(),
            'solicitudes' => $solicitudes,
            'estatusSolicitud' => $estatusSolicitud,
            'tiposPropiedades' => $tiposPropiedades,
            'tiposTramites' => $tiposTramites,
            'destinosObras' => $destinosObras,
            'localidades' => $localidades,
            'tramites' => $tramites,
            'nombreQuery' => $nombreQuery,
            'fechaInicioQuery' => $fechaInicioQuery,
            'fechaFinQuery' => $fechaFinQuery,
            'tiposTramitesQuery' => $tiposTramitesQuery,
            'filtroChkSolicitudes' => $filtroChkSolicitudes      
        ]);
    }

    public function deleteCroquis(Request $request, $idSolicitud)
    {
        $solicitud = Solicitud::findOrFail($idSolicitud);
        $propiedad = $solicitud->propiedad;
        $imgCroquisPropiedad = $propiedad->img_croquis;

        if ($propiedad->editable)
        { 
            $propiedad->img_croquis = null;                
            $propiedad->save();
        }
        else
        {
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

            Propiedad::where('clave_catastral', trim($request->claveCatastral))
            ->update(['activa' => 0]);  //Se ponen inactivas todas las propiedades con la clave catastral

            $propiedad = Propiedad::create([
                'id_tipo' => $request->idTipoPropiedad,
                'clave_catastral' => trim($request->claveCatastral),
                'calle' => $request->callePropiedad !== null ? trim(mb_strtoupper($request->callePropiedad)) : null,
                'numero' => $request->numeroPropiedad !== null ? trim(mb_strtoupper($request->numeroPropiedad)) : null,
                'id_colonia' => $request->idColoniaPropiedad,
                'id_localidad' => $request->idLocalidadPropiedad,
                'superficie' => $request->superficiePropiedad,
                'superficie_construccion' => $request->superficieConstruccionPropiedad,
                'id_contacto' => $request->idContactoPropiedad,
            ]);

            $solicitud->id_propiedad = $propiedad->id;
            $solicitud->save();
        }

        if (Storage::disk('public')->exists('croquis/' . $imgCroquisPropiedad)) 
        {
            $solicitud->load(['contacto', 'propiedad', 'propiedad.contacto']);
            Storage::disk('public')->delete('croquis/' . $imgCroquisPropiedad);
            return back()->with('success', 'Imagen borrada con éxito')
            ->with('solicitud', $solicitud);
        }
        else
        {
            return back()->with('error', 'No existe el archivo de la imagen deseada.')
            ->with('solicitud', $solicitud);
        }
    }

    public function uploadCroquis(Request $request, $idSolicitud)
    {
        if ($request->hasFile('archivo')) 
        {
            DB::beginTransaction();

            try {
                $archivo = $request->file('archivo');

                $idSolicitudCeros = str_pad($idSolicitud % 1000000, 6, '0', STR_PAD_LEFT);
                $nombreArchivo = $request->claveCatastral . '_' . $idSolicitudCeros . '_' . Str::random(3);
                $extension = $archivo->getClientOriginalExtension();
                $nombreArchivoCroquis = $nombreArchivo . '.' . $extension;

                $manager = new ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                $imagen = $manager->read($archivo->getPathname());
                $imagen->scale(width: 400);

                $contenido = match (strtolower($extension)) {
                    'png' => $imagen->toPng()->toString(),
                    'webp' => $imagen->toWebp()->toString(),
                    default => $imagen->toJpeg()->toString(),
                };

                Storage::disk('public')->put('croquis/' . $nombreArchivoCroquis, $contenido);

                $solicitud = Solicitud::findOrFail($idSolicitud);
                $solicitud->load(['contacto', 'propiedad', 'propiedad.contacto']);
                $propiedad = $solicitud->propiedad;

                $propiedad->img_croquis = $nombreArchivoCroquis;
                $propiedad->save();

                DB::commit();

                return back()->with('success', 'Croquis subido con éxito')
                ->with('solicitud', $solicitud);;

            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Error al guardar croquis: ' . $e->getMessage());

                return back()->with('error', 'Ocurrió un error al subir el croquis.');
            }
        }

        return back()->withErrors(['archivo' => 'No se subió ningún archivo']);
    }
    

    public function validaSolicitud(Request $request)
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

        if ($request->telefonoSolicitante === 'null') {
            $request->merge(['telefonoSolicitante' => null]);
        }

        if ($request->emailSolicitante === 'null') {
            $request->merge(['emailSolicitante' => null]);
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

        if ($request->idDestinoObra === 'null') {
            $request->merge(['idDestinoObra' => null]);
        }

        if ($esCurpPropietarioInvalida)
        {
            $errors = new MessageBag(['curpPropietario' => ['La CURP del PROPIETARIO es inválida.']]);
            $this->activeTab = 'propietario';

            return $errors;
        }

        if ($request->claveCatastral === null)
        {
            $errors = new MessageBag(['claveCatastral' => ['La CLAVE CATASTRAL es obligatoria.']]);
            $this->activeTab = 'propiedad';

            return $errors;
        }
        else
        {
            $validator = Validator::make($request->all(),[
                'claveCatastral' => 'digits:18',             
                'idEstatusSolicitud' => 'required',
            ], [
                'claveCatastral.digits' => '<li> La CLAVE CATASTRAL está incompleta. </li>',                
                'idEstatusSolicitud.required' => '<li> El ESTATUS de la SOLICITUD es obligatorio. </li>',
            ]);

            if ($validator->fails()) 
            {
                $this->activeTab = 'propiedad';

                return $validator; // Envía los errores a la vista
            }
        }

        $validator = Validator::make($request->all(),[
            'curpPropietario' => 'required|string|size:18',
            'nomPropietario' => 'required|string|max:30',
            'apePropietario' => 'required|string|max:40',
            'emailPropietario' => 'nullable|email',
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
            // 'callePropietario.max' => '<li> La CALLE del PROPIETARIO no puede tener más de 60 caracteres. </li>',
        ]);

        if ($validator->fails())
        {
            $this->activeTab = 'propietario';
            return $validator; // Envía los errores a la vista
        }
        
        if ($request->telefonoPropietario !== null && $request->telefonoPropietario !== "null" && !preg_match('/^\d{10}$/', $request->telefonoPropietario)) {
            $errors = new MessageBag(['telefonoPropietario' => ['El TELÉFONO del PROPIETARIO debe tener exactamente 10 dígitos.']]);
            $this->activeTab = 'propietario';
            
            return $errors;
        }

        if (!$esSolicitante)
        {
            if ($request->curpSolicitante && !$esCurpSolicitanteInvalida)
            {
                $validator = Validator::make($request->all(),[
                    'curpSolicitante' => 'required|string|size:18',
                    'nomSolicitante' => 'required|string|max:30',
                    'apeSolicitante' => 'required|string|max:40',
                ], [
                    'curpSolicitante.required' => '<li> La CURP del SOLICITANTE es obligatoria. </li>',
                    'curpSolicitante.size' => '<li> La longitud de la CURP del SOLICITANTE debe ser 18 caracteres. </li>',
                    'nomSolicitante.required' => '<li> El campo NOMBRE del SOLICITANTE es obligatorio. </li>',
                    'nomSolicitante.string' => '<li> El NOMBRE del SOLICITANTE debe ser una cadena de texto válida. </li>',
                    'nomSolicitante.max' => '<li> El NOMBRE del SOLICITANTE no puede tener más de 30 caracteres. </li>',
                    'apeSolicitante.required' => '<li> El campo APELLIDOS del SOLICITANTE es obligatorio. </li>',
                    'apeSolicitante.string' => '<li> Los APELLIDOS del SOLICITANTE deben ser una cadena de texto válida. </li>',
                    'apeSolicitante.max' => '<li> Los APELLIDOS del SOLICITANTE no pueden tener más de 40 caracteres. </li>',
                ]);
            }

            if ($validator->fails()) 
            {
                $this->activeTab = 'solicitante';

                return $validator; // Envía los errores a la vista
            }
        }

        if ($request->idEstatusSolicitud > 6) 
        {            
            if (!$request->croquis)
            {
                $this->activeTab = 'croquis';

                if ($request->imgCroquisPropiedad === null) 
                {
                    $errors = new MessageBag(['croquis' => ['El CROQUIS es obligatorio.']]);
                    return $errors;
                }
                else
               {  
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

            $validator = Validator::make($request->all(),[
                'tipoPropiedad' => 'required',
                'superficiePropiedad' => 'required|numeric',    
                'callePropiedad' => 'required|string|max:35',
                'numeroPropiedad' => 'required|string|max:8',
                'idLocalidadPropiedad' => 'required',
            ], [
                'tipoPropiedad.required' => '<li> El TIPO de PROPIEDAD es obligatorio. </li>',
                'superficiePropiedad.required' => '<li>La SUPERFICIE de la PROPIEDAD es obligatoria.</li>',
                'superficiePropiedad.numeric' => '<li>La SUPERFICIE de la PROPIEDAD debe ser un número.</li>',
                
                'callePropiedad.required' => '<li>La CALLE de la PROPIEDAD es obligatoria.</li>',
                'callePropiedad.string' => '<li>La CALLE de la PROPIEDAD debe ser texto.</li>',
                'callePropiedad.max' => '<li>La CALLE de la PROPIEDAD no debe exceder los 35 caracteres.</li>',
                'numeroPropiedad.required' => '<li>El NÚMERO de la PROPIEDAD es obligatorio.</li>',
                'numeroPropiedad.string' => '<li>El NÚMERO de la PROPIEDAD debe ser texto.</li>',
                'numeroPropiedad.max' => '<li>El NÚMERO de la PROPIEDAD no debe exceder los 8 caracteres.</li>',
                'idLocalidadPropiedad.required' => '<li>La LOCALIDAD de la PROPIEDAD es obligatoria.</li>',
            ]);

            if ($validator->fails()) {
                $this->activeTab = 'propiedad';
                return $validator; // Envía los errores a la vista
            }

            if ($request->tipoPropiedad === '2')
            {
                $validator = Validator::make($request->all(),[
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

            $validator = Validator::make($request->all(),[
                    'telefonoPropietario' => 'required'
            ], [

                    'telefonoPropietario.required' => '<li>El TELÉFONO del PROPIETARIO es obligatorio.</li>',
            ]);

            if ($validator->fails()) 
            {
                $this->activeTab = 'propietario';

                return $validator; // Envía los errores a la vista
            }

            if (!$esSolicitante)
            { 
                $validator = Validator::make($request->all(),[
                        'telefonoSolicitante' => 'required'
                ], [

                        'telefonoSolicitante.required' => '<li>El TELÉFONO del SOLICITANTE es obligatorio.</li>',
                ]);

                if ($validator->fails()) 
                {
                    $this->activeTab = 'solicitante';

                    return $validator; // Envía los errores a la vista
                }
            }

            if (empty($request->tramitesSeleccionados))
            {
                $errors = new MessageBag(['tramitesSeleccionados' => ['Selecciona al menos un TRÁMITE para poder continuar.']]);
                
                $this->activeTab = 'tramite';
                
                return $errors;
            }

            if ($request->idDestinoObra === null)
            {
                $errors = new MessageBag(['idDestinoObra' => ['El DESTINO de OBRA es obligatorio.']]);
                
                $this->activeTab = 'tramite';
                
                return $errors;
            }
        }
    }

    public function regresaPersonaPropietario(Request $request)
    {
        $personaPropietario = $request->idPersonaPropietario ? Persona::find($request->idPersonaPropietario) : null;
        if ($personaPropietario)  //Si ya existe
        {
            if ($personaPropietario->editable)  //Si se puede editar
            {   //Si hay cambios entonces actualiza el campo correspondiente
                if ($personaPropietario->nombre != trim(mb_strtoupper($request->nomPropietario)))
                {
                    $personaPropietario->nombre = trim(mb_strtoupper($request->nomPropietario));
                }
                if ($personaPropietario->apellidos != trim(mb_strtoupper($request->apePropietario)))
                {
                    $personaPropietario->apellidos = trim(mb_strtoupper($request->apePropietario));
                }
                $personaPropietario->save();
            }
            else
            {
                $personaPropietario = $this->regresaPersonaPropietarioActiva($request);
            }
        }
        else  //Si no existe la persona busca o agrega la persona activa
        {
            $personaPropietario = $this->regresaPersonaPropietarioActiva($request);
        }

        return $personaPropietario;
    }

    public function regresaPersonaPropietarioActiva(Request $request)
    {
        $personaPropietario = Persona::where('curp', trim(mb_strtoupper($request->curpPropietario)))
                                    ->where('nombre', trim(mb_strtoupper($request->nomPropietario)))
                                    ->where('apellidos', trim(mb_strtoupper($request->apePropietario)))
                                    ->first();

        if ($personaPropietario)  //Si la hay y no es activa entonces la activa
        {
            if (!$personaPropietario->activa)
            { 
                Persona::where('curp', $request->curpPropietario)
                ->update(['activa' => 0]);  //Se ponen inactivas todas las personas con ese curp

                $personaPropietario->activa = 1;
                $personaPropietario->save();
            }
        }
        else  //Si no, entonces la crea
        { 
            Persona::where('curp', $request->curpPropietario)
            ->update(['activa' => 0]);  //Se ponen inactivas todas las personas con ese curp

            $personaPropietario = Persona::create([
                'curp' => trim(mb_strtoupper($request->curpPropietario)),
                'nombre' => trim(mb_strtoupper($request->nomPropietario)),
                'apellidos' => trim(mb_strtoupper($request->apePropietario)),
            ]);
        }

        return $personaPropietario;
    }

    public function regresaContactoPropietario(Request $request, $personaPropietario)
    {
        // $contactoPropietario = Contacto::where('id_persona', $personaPropietario->id)
        //                                ->where('activo', true)->first();

        $contactoPropietario = $request->idContactoPropietario ? Contacto::find($request->idContactoPropietario) : null;

        if ($contactoPropietario) //Si ya existe
        {
            if ($contactoPropietario->editable) //Si se pueden editar los datos del propietario
            {   //Si hay cambios entonces actualiza el campo correspondiente
                if ($contactoPropietario->telefono != trim($request->telefonoPropietario))
                {
                    $contactoPropietario->telefono = trim($request->telefonoPropietario);
                }
                if ($contactoPropietario->email != trim(mb_strtolower($request->emailPropietario)))
                {
                    $contactoPropietario->email = $request->emailPropietario !== null ? trim(mb_strtolower($request->emailPropietario)) : null;
                }
                $contactoPropietario->save();
            }
            else  //Si no se pueden editar los campos
            {
                $contactoPropietario = $this->regresaContactoPropietarioActivo($request, $personaPropietario);
            }
        }
        else  //Si no existe el contacto del propietario lo crea
        {
            $contactoPropietario = $this->regresaContactoPropietarioActivo($request, $personaPropietario);
        }

        return $contactoPropietario;
    }

    public function regresaContactoPropietarioActivo(Request $request, $personaPropietario)
    {
        //Busca al propietario con el TELÉFONO y EMAIL 
        $contactoPropietario = Contacto::
            where('telefono', trim($request->telefonoPropietario))->
            when($request->emailPropietario !== null, function ($query) use ($request) {
            $query->where('email', trim($request->emailPropietario));
        })->first();

        if ($contactoPropietario)  //Si existe entonces pregunta si es el activo
        {
            if (!$contactoPropietario->activo)  //Si no es activo
            {
                Contacto::where('id_persona', $personaPropietario->id)
                ->update(['activo' => 0]);  //Se ponen inactivas todos los contactos de la persona propietaria

                $contactoPropietario->activo = 1;  //Si no está activa hay que activarla
                $contactoPropietario->save();
            }
        }
        else  //Si no existe es que se ha cambiado el TELÉFONO o el EMAIL
        {
            Contacto::where('id_persona', $personaPropietario->id)
            ->update(['activo' => 0]);  //Se ponen inactivas todos los contactos de la persona propietaria

            //Se crea un nuevo propietario, el cual será el ACTIVO  
            $contactoPropietario = Contacto::create([
                'id_persona' => $personaPropietario->id,
                'telefono' => trim($request->telefonoPropietario),
                'email' => $request->emailPropietario !== null ? trim(mb_strtolower($request->emailPropietario)) : null,
            ]);
        }
        return $contactoPropietario;
    }

    public function regresaPersonaSolicitante(Request $request, $personaPropietario = null)
    {
        if ($personaPropietario)
        { 
            $personaSolicitante = $personaPropietario;
        }
        else
        {
            $personaSolicitante = $request->idPersonaSolicitante ? Persona::find($request->idPersonaSolicitante) : null;

            if ($personaSolicitante)  //Si ya existe
            {
                if ($personaSolicitante->editable)  //Si se puede editar
                {   //Si hay cambios entonces actualiza el campo correspondiente
                    if ($personaSolicitante->nombre != trim(mb_strtoupper($request->nomSolicitante)))
                    {
                        $personaSolicitante->nombre = trim(mb_strtoupper($request->nomSolicitante));
                    }
                    if ($personaSolicitante->apellidos != trim(mb_strtoupper($request->apeSolicitante)))
                    {
                        $personaSolicitante->apellidos = trim(mb_strtoupper($request->apeSolicitante));
                    }
                    $personaSolicitante->save();
                }
                else
                {
                    $personaSolicitante = $this->regresaPersonaSolicitanteActiva($request);
                }
            }
            else  //Si no existe la persona busca o agrega la persona activa
            {
                $personaSolicitante = $this->regresaPersonaSolicitanteActiva($request);
            }
        }
        
        return $personaSolicitante;
    } 

    public function regresaPersonaSolicitanteActiva(Request $request)
    {
        $personaSolicitante = Persona::where('curp', trim(mb_strtoupper($request->curpSolicitante)))
                                    ->where('nombre', trim(mb_strtoupper($request->nomSolicitante)))
                                    ->where('apellidos', trim(mb_strtoupper($request->apeSolicitante)))
                                    ->first();

        if ($personaSolicitante)  //Si la hay y no es activa entonces la activa
        {
            if (!$personaSolicitante->activa)
            { 
                Persona::where('curp', $request->curpSolicitante)
                ->update(['activa' => 0]);  //Se ponen inactivas todas las personas con ese curp

                $personaSolicitante->activa = 1;
                $personaSolicitante->save();
            }
        }
        else  //Si no, entonces la crea
        { 
            Persona::where('curp', $request->curpSolicitante)
            ->update(['activa' => 0]);  //Se ponen inactivas todas las personas con ese curp

            $personaSolicitante = Persona::create([
                'curp' => trim(mb_strtoupper($request->curpSolicitante)),
                'nombre' => trim(mb_strtoupper($request->nomSolicitante)),
                'apellidos' => trim(mb_strtoupper($request->apeSolicitante)),
            ]);
        }

        return $personaSolicitante;
    }
    
    public function regresaContactoSolicitante(Request $request, $personaSolicitante, $contactoPropietario = null)
    {
        if ($contactoPropietario)
        {
            $contactoSolicitante = $contactoPropietario;
        }
        else
        {
            //$contactoSolicitante = Contacto::where('id_persona', $personaSolicitante->id)
                                // ->where('activo', true)->first();
            $contactoSolicitante = $request->idContactoSolicitante ? Contacto::find($request->idContactoSolicitante) : null;
            

            if ($contactoSolicitante) //Si ya existe
            {
                if ($contactoSolicitante->editable) //Si se pueden editar los datos del solicitante
                {   //Si hay cambios entonces actualiza el campo correspondiente
                    if ($contactoSolicitante->telefono != trim($request->telefonoSolicitante))
                    {
                        $contactoSolicitante->telefono = trim($request->telefonoSolicitante);
                    }
                    if ($contactoSolicitante->email != trim(mb_strtolower($request->emailSolicitante)))
                    {
                        $contactoSolicitante->email = $request->emailSolicitante !== null ? trim(mb_strtolower($request->emailSolicitante)) : null;
                    }
                    $contactoSolicitante->save();
                }
                else  //Si no se pueden editar los campos
                {
                    $contactoSolicitante = $this->regresaContactoSolicitanteActivo($request, $personaSolicitante);
                }
            }
            else  //Si no existe el contacto del solicitante lo crea
            {
                $contactoSolicitante = $this->regresaContactoSolicitanteActivo($request, $personaSolicitante);
            }
        }
        return $contactoSolicitante;
    }
    
    public function regresaContactoSolicitanteActivo(Request $request, $personaSolicitante)
    {
        //Busca al propietario con el TELÉFONO y EMAIL 
        $contactoSolicitante = Contacto::
            where('telefono', trim($request->telefonoSolicitante))->
            when($request->emailSolicitante !== null, function ($query) use ($request) {
            $query->where('email', trim($request->emailSolicitante));
        })->first();

        if ($contactoSolicitante)  //Si existe entonces pregunta si es el activo
        {
            if (!$contactoSolicitante->activo)  //Si no es activo
            {
                Contacto::where('id_persona', $personaSolicitante->id)
                ->update(['activo' => 0]);  //Se ponen inactivas todos los contactos de la persona solicitante

                $contactoSolicitante->activo = 1;  //Si no está activa hay que activarla
                $contactoSolicitante->save();
            }
        }
        else  //Si no existe es que se ha cambiado el TELÉFONO o el EMAIL
        {
            Contacto::where('id_persona', $personaSolicitante->id)
            ->update(['activo' => 0]);  //Se ponen inactivas todos los contactos de la persona solicitante

            //Se crea un nuevo Solicitante, el cual será el ACTIVO  
            $contactoSolicitante = Contacto::create([
                'id_persona' => $personaSolicitante->id,
                'telefono' => trim($request->telefonoSolicitante),
                'email' => $request->emailSolicitante !== null ? trim(mb_strtolower($request->emailSolicitante)) : null,
            ]);
        }
        return $contactoSolicitante;
    }

    public function regresaPropiedad(Request $request, $contactoPropietario)
    {
        if ($request->idPropiedadSolicitud) 
        {
            $propiedad = Propiedad::findOrFail($request->idPropiedadSolicitud);

            if ($propiedad)
            {
                if ($propiedad->editable)
                {
                    if ($propiedad->id_tipo != $request->tipoPropiedad)
                    {
                        $propiedad->id_tipo = $request->tipoPropiedad;
                    }
                    if ($propiedad->calle != trim(mb_strtoupper($request->callePropiedad)))
                    {
                        $propiedad->calle = $request->callePropiedad !== null ? trim(mb_strtoupper($request->callePropiedad)) : null;
                    }
                    if ($propiedad->numero != trim(mb_strtoupper($request->numeroPropiedad)))
                    {
                        $propiedad->numero = $request->numeroPropiedad !== null ? trim(mb_strtoupper($request->numeroPropiedad)) : null;
                    }
                    if ($propiedad->id_colonia != trim(mb_strtoupper($request->idColoniaPropiedad)))
                    {
                        $propiedad->id_colonia = $request->idColoniaPropiedad !== null ? trim(mb_strtoupper($request->idColoniaPropiedad)) : null;
                    }
                    if ($propiedad->id_localidad != trim(mb_strtoupper($request->idLocalidadPropiedad)))
                    {
                        $propiedad->id_localidad = $request->idLocalidadPropiedad !== null ? trim(mb_strtoupper($request->idLocalidadPropiedad)) : null;
                    }
                    if ($propiedad->superficie != trim(mb_strtoupper($request->superficiePropiedad)))
                    {
                        $propiedad->superficie = $request->superficiePropiedad !== null ? trim(mb_strtoupper($request->superficiePropiedad)) : null;
                    }
                    if ($request->tipoPropiedad === 2 && $propiedad->superficie_construccion != trim(mb_strtoupper($request->superficieConstruccionPropiedad)))
                    {
                        if ($propiedad->superficie_construccion != trim(mb_strtoupper($request->superficieConstruccionPropiedad)))
                        {
                            $propiedad->superficie_construccion = $request->superficieConstruccionPropiedad !== null ? trim(mb_strtoupper($request->superficieConstruccionPropiedad)) : null;
                        }
                    }
                    if ($propiedad->id_contacto != $contactoPropietario->id)
                    {
                        $propiedad->id_contacto = $contactoPropietario->id;
                    }
                    $propiedad->save();
                }
                else
                {
                    $propiedad = $this->regresaPropiedadActiva($request, $contactoPropietario);
                }
            }                
        }
        else 
        {
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
        ->where('id_contacto', $contactoPropietario->id)
        ->first();

        if ($propiedad)  //Si existe entonces la pone como no editable
        {
            if (!$propiedad->activa)  //Si no está activa es porque alguna vez estuvo activa
            {
                Propiedad::where('clave_catastral', trim($request->claveCatastral))
                ->update(['activa' => 0]);  //Se ponen inactivas todas las propiedades con la clave catastral

                $propiedad->activa = 1;  //Si no está activa hay que activarla
                $propiedad->save();
            }
        }
        else  //Si NO EXISTE es porque cambió algún dato
        {
            Propiedad::where('clave_catastral', trim($request->claveCatastral))
            ->update(['activa' => 0]);  //Se ponen inactivas todas las propiedades con la clave catastral

            $propiedad = Propiedad::create([
                'clave_catastral' => trim($request->claveCatastral),
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


    public function store(Request $request)  //Guarda por primera vez la solicitud
    {
        $errores = $this->validaSolicitud($request);

        if ($errores) 
        {
            return back()->withErrors($errores)->with('activeTab', $this->activeTab);
        }

        $tramitesSeleccionados = $request->tramitesSeleccionados;
        
        try {
            DB::beginTransaction(); // Inicia la transacción

            $personaPropietario = $this->regresaPersonaPropietario($request);
            $contactoPropietario = $this->regresaContactoPropietario($request, $personaPropietario);

            $esSolicitante = filter_var($request->esSolicitante, FILTER_VALIDATE_BOOLEAN);
            if ($esSolicitante) //Si el solicitante es el propietario
            {
                $personaSolicitante = $this->regresaPersonaSolicitante($request, $personaPropietario);
                $contactoSolicitante = $this->regresaContactoSolicitante($request, $personaSolicitante, $contactoPropietario);             
            }
            else
            {
                $personaSolicitante = $this->regresaPersonaSolicitante($request);
                $contactoSolicitante = $this->regresaContactoSolicitante($request, $personaSolicitante);             
            }    
           
            $propiedad = $this->regresaPropiedad($request, $contactoPropietario); 

            $solicitud = Solicitud::create([
                'id_contacto' => $contactoSolicitante->id,
                'id_propiedad' => $propiedad->id,
                'id_destino_obra' => $request->idDestinoObra,
                'id_estatus' => $request->idEstatusSolicitud,
                'fecha_ingreso' => $request->fecha_ingreso,
            ]);

            $folioDigital = Str::random(25);

            // Después de crear la solicitud, asigna el ID al folio y guarda los cambios
            $solicitud->folio_digital = $folioDigital;

            if (!empty($tramitesSeleccionados)) {
                foreach ($tramitesSeleccionados as $tramiteId) {
                    SolicitudTramite::create([
                        'id_solicitud' => $solicitud->id,
                        'id_tramite' => $tramiteId,
                    ]);
                }
            }

            $idSolicitud = $solicitud->id;
            $solicitud->save();

            $solicitud->load(['contacto', 'propiedad', 'propiedad.contacto']);

            DB::commit(); // Confirma la transacción si todo salió bien
        
            return back()->with('success', 'Solicitud N° ' . str_pad($solicitud->id, 4, '0', STR_PAD_LEFT) . ' creada exitosamente')
             ->with('solicitudes', Solicitud::all())
             ->with('idSolicitud', $idSolicitud)
             ->with('solicitud', $solicitud);
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte la transacción si ocurre un error
            return response()->json(['error' => 'Hubo un error al crear la solicitud: ' . $e->getMessage()], 500);
        }
    }

   
    public function update(Request $request, $id)
    {
        $errores = $this->validaSolicitud($request);

        if ($errores) 
        {
            return back()->withErrors($errores)->with('activeTab', $this->activeTab);
        }

        DB::beginTransaction();
        try 
        {
            $this->validaSolicitud($request);      

            $personaPropietario = $this->regresaPersonaPropietario($request);
            $contactoPropietario = $this->regresaContactoPropietario($request, $personaPropietario);

            $esSolicitante = filter_var($request->esSolicitante, FILTER_VALIDATE_BOOLEAN);
            if ($esSolicitante) //Si el solicitante es el propietario
            {
                $personaSolicitante = $this->regresaPersonaSolicitante($request, $personaPropietario);
                $contactoSolicitante = $this->regresaContactoSolicitante($request, $personaSolicitante, $contactoPropietario);             
            }
            else
            {
                $personaSolicitante = $this->regresaPersonaSolicitante($request);
                $contactoSolicitante = $this->regresaContactoSolicitante($request, $personaSolicitante);             
            }

            $propiedad = $this->regresaPropiedad($request, $contactoPropietario);

            $solicitud = Solicitud::findOrFail($id);
           
            $solicitud->id_propiedad = $propiedad->id;
            $solicitud->id_contacto = $contactoSolicitante->id;
            $solicitud->id_destino_obra = $request->idDestinoObra;
            $solicitud->id_estatus = $request->idEstatusSolicitud;
            $solicitud->fecha_ingreso = $request->fecha_ingreso;

            SolicitudTramite::where('id_solicitud', $id)->delete();
            if (!empty($request->tramitesSeleccionados)) {
                foreach ($request->tramitesSeleccionados as $tramiteId) {
                    SolicitudTramite::create([
                        'id_solicitud' => $id,
                        'id_tramite' => $tramiteId,
                    ]);
                }
            }
            
            if ($request->idEstatusSolicitud == 99)   //Cuando es un TRÁMITE CONCLUÍDO
            {
                $personaPropietario->editable = 0;
                $personaPropietario->save();

                $personaSolicitante->editable = 0;
                $personaSolicitante->save();

                $contactoPropietario->editable = 0;
                $contactoPropietario->save();

                $contactoSolicitante->editable = 0;
                $contactoSolicitante->save();

                $propiedad->editable = 0; 
            }

            $propiedad->save();
            $solicitud->save();

            DB::commit();

            return back()->with('success', 'Solicitud actualizada con éxito');

        } catch (\Throwable $e) {
            DB::rollBack();

            // Opcional: Log del error o manejo personalizado
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al actualizar la solicitud.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
