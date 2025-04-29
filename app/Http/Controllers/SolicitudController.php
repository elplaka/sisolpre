<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Solicitud;
use App\Models\EstatusSolicitud;
use App\Models\Persona;
use App\Models\Colonia;
use App\Models\Localidad;
use App\Models\TipoPropiedad;
use App\Models\DestinoObra;
use App\Models\TipoTramite;
use App\Models\Solicitante;
use App\Models\Propiedad;
use App\Models\SolicitudTramite;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;

class SolicitudController extends Controller
{
    public function getPersona($curp)
    {
        $persona = Persona::with('solicitante', 'solicitante.colonia', 'solicitante.localidad')
        ->where('curp', $curp)->get();

        return response()->json([
            'persona' => $persona
        ]);
    }

    public function getPropiedad($claveCatastral)
    {
        $propiedad = Propiedad::with('colonia', 'localidad', 'tipo', 'solicitudes')
        ->where('clave_catastral', $claveCatastral)
        ->orderByDesc('id')
        ->first();

        $croquis = $propiedad?->solicitudes()->latest()->value('img_croquis') ?? '';

        return response()->json([
            'propiedad' => $propiedad,
            'croquis' => $croquis,
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
        $selectedStatuses = $request->input('selectedStatuses', []); // Estatus seleccionados
        $isActive = filter_var($request->input('isActive'), FILTER_VALIDATE_BOOLEAN); // Propiedades activas
        $isInactive = filter_var($request->input('isInactive'), FILTER_VALIDATE_BOOLEAN); // Propiedades inactivas

        $sortColumn = $request->input('sortColumn', 'id'); // Columna de ordenación
        $sortDirection = $request->input('sortDirection', 'asc'); // Dirección de ordenación

        $searchQuery = $request->input('query');
        if ($searchQuery) {
            $solicitudesQuery = Solicitud::join('estatus_solicitudes', 'solicitudes.id_estatus', '=', 'estatus_solicitudes.id')
                ->with(['estatus', 'solicitante', 'propiedad', 'tramites', 'tramites.tramite', 'solicitante.persona', 'solicitante.colonia', 'solicitante.localidad', 'propiedad.colonia', 'propiedad.localidad', 'propiedad.tipo'])
                ->select('solicitudes.*')
                ->where(function ($query) use ($searchQuery) {
                    $query->where('solicitantes.curp', 'like', '%' . $searchQuery . '%')
                        ->orWhere('solicitantes.calle', 'like', '%' . $searchQuery . '%')
                        ->orWhere('propiedades.clave_catastral', 'like', '%' . $searchQuery . '%')
                        ->orWhere('estatus_solicitudes.nombre', 'like', '%' . $searchQuery . '%');
                });

            if ($sortColumn == 'estatus_solicitudes.nombre') {
                $solicitudesQuery->orderBy('estatus_solicitudes.nombre', $sortDirection)
                                ->orderBy('solicitudes.fecha_ingreso', $sortDirection);
            } else {
                $solicitudesQuery->orderBy($sortColumn, $sortDirection);
            }

            if (!empty($selectedStatuses)) {
                $solicitudesQuery->whereIn('id_estatus', $selectedStatuses);
            }

            $solicitudesQuery->where(function ($query) use ($isActive, $isInactive) {
                if ($isActive) {
                    $query->orWhere('propiedades.activo', true);
                }
                if ($isInactive) {
                    $query->orWhere('propiedades.activo', false);
                }
            });

            $solicitudes = $solicitudesQuery->paginate(10);
            $activeCount = Solicitud::join('propiedades', 'solicitudes.id_propiedad', '=', 'propiedades.id')
                                    ->where('propiedades.activo', true)
                                    ->count();
            $inactiveCount = Solicitud::join('propiedades', 'solicitudes.id_propiedad', '=', 'propiedades.id')
                                    ->where('propiedades.activo', false)
                                    ->count();
        } else {
            $solicitudesQuery = Solicitud::join('estatus_solicitudes', 'solicitudes.id_estatus', '=', 'estatus_solicitudes.id')
                ->with(['estatus', 'solicitante', 'propiedad', 'tramites', 'tramites.tramite', 'solicitante.persona', 'solicitante.colonia', 'solicitante.localidad', 'propiedad.colonia', 'propiedad.localidad', 'propiedad.tipo'])
                ->select('solicitudes.*');

            if ($sortColumn == 'estatus_solicitudes.nombre') {
                $solicitudesQuery->orderBy('estatus_solicitudes.nombre', $sortDirection)
                                ->orderBy('solicitudes.fecha_ingreso', $sortDirection);
            } else {
                $solicitudesQuery->orderBy($sortColumn, $sortDirection);
            }

            $activeCount = Solicitud::join('propiedades', 'solicitudes.id_propiedad', '=', 'propiedades.id')
                                    ->count();
            $inactiveCount = Solicitud::join('propiedades', 'solicitudes.id_propiedad', '=', 'propiedades.id')
                                    ->count();

            $solicitudes = $solicitudesQuery->paginate(10);
        }

        // Obtener estatus de solicitudes con conteo basado en los filtros
        $statuses = EstatusSolicitud::withCount(['solicitudes as solicitudes_count' => function ($query) use ($solicitudesQuery) {
            $query->whereIn('id', $solicitudesQuery->pluck('id')); // Filtrar por solicitudes actuales
        }])->orderBy('nombre')->get();

        $tiposPropiedades = TipoPropiedad::orderBy('nombre', 'asc')->get();
        $destinosObras = DestinoObra::orderBy('nombre', 'asc')->get();
        $tiposTramites = TipoTramite::with('tramites')->where('activo', true)->get();
        $estatusSolicitud = EstatusSolicitud::where('id', '>', true)->where('activo', true)->get();

        return Inertia::render('Solicitudes/Index', [
            'userAuth' => Auth::user(),
            'solicitudes' => $solicitudes,
            'estatusSolicitud' => $estatusSolicitud,
            'tiposPropiedades' => $tiposPropiedades,
            'tiposTramites' => $tiposTramites,
            'destinosObras' => $destinosObras,
            'statuses' => $statuses,
            'searchQuery' => $searchQuery,
            'selectedStatuses' => $selectedStatuses,
            'isActive' => $isActive,
            'isInactive' => $isInactive,
            'activos' => $activeCount,
            'inactivos' => $inactiveCount,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->emailSolicitante === 'null') {
            $request->merge(['emailSolicitante' => null]);
        }

        if ($request->calleSolicitante === 'null') {
            $request->merge(['calleSolicitante' => null]);
        }

        if ($request->idColoniaSolicitante === 'null') {
            $request->merge(['idColoniaSolicitante' => null]);
        }

        $esCurpInvalida = filter_var($request->curpInvalida, FILTER_VALIDATE_BOOLEAN);

        if ($esCurpInvalida)
        {
            return back()->withErrors([
                'curp' => '<li> La CURP del SOLICITANTE es inválida. </li>',
            ])->withInput();
        }
        else
        {
            $request->validate([
                'curp' => 'required|string|size:18',
            ], [
                'curp.required' => '<li> La CURP del SOLICITANTE es obligatoria. </li>',
                'curp.size' => '<li> La longitud de la CURP del SOLICITANTE debe ser 18 caracteres. </li>',
            ]);
        }

        if ($request->calleSolicitante !== null && $request->calleSolicitante !== '') {
            $request->validate([
                'numeroSolicitante' => 'required|string|max:10',
                'idLocalidadSolicitante' => 'required',
                'idEstatusSolicitud' =>  'required'
            ], [
                'numeroSolicitante.required' => '<li> El NÚMERO del SOLICITANTE es obligatorio si se proporciona una CALLE del SOLICITANTE. </li>',
                'numeroSolicitante.string' => '<li> El NÚMERO del SOLICITANTE debe ser una cadena de texto válida. </li>',
                'numeroSolicitante.max' => '<li> El NÚMERO del SOLICITANTE no puede tener más de 10 caracteres. </li>',
                'idLocalidadSolicitante.required' => '<li> La LOCALIDAD del SOLICITANTE es obligatoria si se proporciona una CALLE del SOLICITANTE. </li>',
                'idEstatusSolicitud.required' => '<li> El ESTATUS de la SOLICITUD es obligatorio. </li>',
            ]);
        }

        if ($request->claveCatastral !== null && strlen(trim($request->claveCatastral)) > 6) {
            $request->validate([
                'claveCatastral' => 'digits:18',
            ], [
                'claveCatastral.digits' => '<li> La CLAVE CATASTRAL está incompleta. </li>',
            ]);
        }

        $request->validate([
            'nomSolicitante' => 'required|string|max:30',
            'apeSolicitante' => 'required|string|max:40',
            'telefonoSolicitante' => 'nullable',
            'emailSolicitante' => 'nullable|email',
            'calleSolicitante' => 'nullable|string|max:60',
        ], [
            'nomSolicitante.required' => '<li> El campo NOMBRE del SOLICITANTE es obligatorio. </li>',
            'nomSolicitante.string' => '<li> El NOMBRE del SOLICITANTE debe ser una cadena de texto válida. </li>',
            'nomSolicitante.max' => '<li> El NOMBRE del SOLICITANTE no puede tener más de 30 caracteres. </li>',
            'apeSolicitante.required' => '<li> El campo APELLIDOS del SOLICITANTE es obligatorio. </li>',
            'apeSolicitante.string' => '<li> Los APELLIDOS del SOLICITANTE deben ser una cadena de texto válida. </li>',
            'apeSolicitante.max' => '<li> Los APELLIDOS del SOLICITANTE no pueden tener más de 40 caracteres. </li>',
            'emailSolicitante.email' => '<li> El CORREO ELECTRÓNICO del SOLICITANTE debe tener un formato válido. </li>',
            'calleSolicitante.max' => '<li> La CALLE del SOLICITANTE no puede tener más de 60 caracteres. </li>',
        ]);
        
        if ($request->telefonoSolicitante !== null && $request->telefonoSolicitante !== "null" && !preg_match('/^\d{10}$/', $request->telefonoSolicitante)) {
            return back()->withErrors([
                'telefonoSolicitante' => 'El TELÉFONO del SOLICITANTE debe tener exactamente 10 dígitos.'
            ]);
        }

        $tramitesSeleccionados = $request->tramitesSeleccionados;
        
        try {
            DB::beginTransaction(); // Inicia la transacción

            $persona = Persona::create([
                'curp' => trim(mb_strtoupper($request->curp)),
                'nombre' => trim(mb_strtoupper($request->nomSolicitante)),
                'apellidos' => trim(mb_strtoupper($request->apeSolicitante)),
            ]);

            $solicitante = Solicitante::create([
                'id_persona' => $persona->id,
                'calle' => trim(mb_strtoupper($request->calleSolicitante)),
                'num_casa' => trim(mb_strtoupper($request->numeroSolicitante)),
                'id_colonia' => $request->idColoniaSolicitante,
                'id_localidad' => $request->idLocalidadSolicitante,
                'telefono' => $request->telefonoSolicitante,
                'email' => $request->emailSolicitante,
            ]);

            $solicitante_prop = $solicitante;

            if (!$request->esPropietario)
            {
                $persona_prop = Persona::create([
                    'curp' => trim(mb_strtoupper($request->curpPropietario)),
                    'nombre' => trim(mb_strtoupper($request->nomPropietario)),
                    'apellidos' => trim(mb_strtolower($request->apePropietario)),
                ]);

                $solicitante_prop = Solicitante::create([
                    'id_persona' => $persona_prop->id,
                    'calle' => trim(mb_strtoupper($request->callePropietario)),
                    'num_casa' => trim(mb_strtoupper($request->numeroPropietario)),
                    'id_colonia' => $request->idColoniaPropietario,
                    'id_localidad' => $request->idLocalidadPropietario,
                    'telefono' => trim(mb_strtoupper($request->telefonoPropietario)),
                    'email' => trim(mb_strtoupper($request->emailPropietario)),
                ]);
            }

            $propiedad = Propiedad::create([
                'id_tipo' => $request->tipoPropiedad,
                'clave_catastral' => trim($request->claveCatastral),
                'calle' => trim(mb_strtoupper($request->callePropiedad)),
                'numero' => trim(mb_strtoupper($request->numeroPropiedad)),
                'id_colonia' => $request->idColoniaPropiedad,
                'id_localidad' => $request->idLocalidadPropiedad,
                'superficie' => $request->superficiePropiedad,
                'superficie_construccion' => $request->superficieConstruccionPropiedad,
            ]);

            $nombreArchivoCroquis = null;
            if ($request->hasFile('croquis')) 
            {
                $archivoCroquis = $request->file('croquis');

                $fechaObjeto = new \DateTime($request->fecha_ingreso);

                $fechaConvertida = $fechaObjeto->format('ymd');

                $nombreArchivo = $propiedad->clave_catastral . '_' . $fechaConvertida . '_' . Str::random(3);
                $extension = $archivoCroquis->getClientOriginalExtension();
                $nombreArchivoCroquis = $nombreArchivo . '.' . $extension;
    
                $manager = new ImageManager(
                    new \Intervention\Image\Drivers\Gd\Driver()
                );

                $imagen = $manager->read($archivoCroquis->getPathname());

                $imagen->scale(width: 400); // 400px

                $contenido = match (strtolower($extension)) {
                    'png' => $imagen->toPng()->toString(),
                    'webp' => $imagen->toWebp()->toString(),
                    default => $imagen->toJpeg()->toString(),
                };

                // Storage::put('croquis/' . $nombreArchivoCroquis, $contenido);
                Storage::disk('public')->put('croquis/' . $nombreArchivoCroquis, $contenido);
            }

            $solicitud = Solicitud::create([
                'id_solicitante' => $solicitante->id,
                'id_propietario' => $solicitante_prop->id,
                'id_propiedad' => $propiedad->id,
                'id_destino_obra' => $request->destinoObra,
                'id_estatus' => $request->idEstatusSolicitud,
                'fecha_ingreso' => $request->fechaIngreso,
                'img_croquis' => $nombreArchivoCroquis,
            ]);

            if (!empty($tramitesSeleccionados)) {
                foreach ($tramitesSeleccionados as $tramiteId) {
                    SolicitudTramite::create([
                        'id_solicitud' => $solicitud->id,
                        'id_tramite' => $tramiteId,
                    ]);
                }
            }

            DB::commit(); // Confirma la transacción si todo salió bien
        
            return back()->with('success', 'Solicitud N° ' . $solicitud->id . ' creada exitosamente')->with('solicitudes', Solicitud::all());
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte la transacción si ocurre un error
            return response()->json(['error' => 'Hubo un error al crear el usuario: ' . $e->getMessage()], 500);
        }
    }
}
