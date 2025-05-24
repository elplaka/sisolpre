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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

class SolicitudController extends Controller
{
    public $activeTab;

    public function getPersona(Request $request, $curp)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($request->query('tipo') === 'solicitante')
        {
            $persona = Persona::with('solicitante', 'solicitante.colonia', 'solicitante.localidad', 'solicitante.persona')
            ->where('curp', $curp)
            ->where('activa', 1)->first();
        }
        elseif ($request->query('tipo') === 'propietario')
        {
            $persona = Persona::with('propietario', 'propietario.colonia', 'propietario.localidad', 'propietario.persona')
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
        $propiedad = Propiedad::with('colonia', 'localidad', 'tipo', 'solicitudes')
        ->where('clave_catastral', $claveCatastral)
        ->where('activa', 1)->first();

        // $croquis = $propiedad?->solicitudes()->latest()->value('img_croquis') ?? null;

        return response()->json([
            'propiedad' => $propiedad,
            // 'croquis' => $croquis,
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
                ->with(['estatus', 'solicitante', 'propietario', 'propiedad', 'destino_obra', 'tramites', 'tramites.tramite', 'solicitante.persona', 'solicitante.colonia', 'solicitante.localidad', 'propietario.persona', 'propietario.colonia', 'propietario.localidad', 'propiedad.colonia', 'propiedad.localidad', 'propiedad.tipo'])
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
                ->with(['estatus', 'solicitante', 'propietario', 'propiedad', 'destino_obra', 'tramites', 'tramites.tramite', 'solicitante.persona', 'solicitante.colonia', 'solicitante.localidad', 'propietario.persona', 'propietario.colonia', 'propietario.localidad','propiedad.colonia', 'propiedad.localidad', 'propiedad.tipo'])
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
        $estatusSolicitud = EstatusSolicitud::where('id', '>', 1)->where('activo', true)->get();
        $localidades = Localidad::get();

        return Inertia::render('Solicitudes/Index', [
            'userAuth' => Auth::user(),
            'solicitudes' => $solicitudes,
            'estatusSolicitud' => $estatusSolicitud,
            'tiposPropiedades' => $tiposPropiedades,
            'tiposTramites' => $tiposTramites,
            'destinosObras' => $destinosObras,
            'statuses' => $statuses,
            'localidades' => $localidades,
            'searchQuery' => $searchQuery,
            'selectedStatuses' => $selectedStatuses,
            'isActive' => $isActive,
            'isInactive' => $isInactive,
            'activos' => $activeCount,
            'inactivos' => $inactiveCount,
        ]);
    }

    public function deleteCroquis(Request $request, $idSolicitud)
    {
        $solicitud = Solicitud::findOrFail($idSolicitud);
        $propiedad = $solicitud->propiedad;
        $imgCroquisPropiedad = $propiedad->img_croquis;

        if (Storage::disk('public')->exists('croquis/' . $imgCroquisPropiedad)) 
        {
            $propiedad->img_croquis = null;
            
            $propiedad->save();
            
            Storage::disk('public')->delete('croquis/' . $imgCroquisPropiedad);
            return back()->with('success', 'Imagen borrada con éxito');
        }
    }

    public function uploadCroquis(Request $request, $idSolicitud)
    {
        if ($request->hasFile('archivo')) 
        {
            $archivo = $request->file('archivo');

            $fechaObjeto = new \DateTime($request->fecha_ingreso);
            $fechaConvertida = $fechaObjeto->format('ymd');
            $nombreArchivo = $request->claveCatastral . '_' . $fechaConvertida . '_' . Str::random(3);
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

            $solicitud = Solicitud::find($idSolicitud);
            $propiedad = $solicitud->propiedad;
            $propiedad->img_croquis = $nombreArchivoCroquis;
            $propiedad->save();

            return back()->with('success', 'Croquis guardado con éxito');
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

        // if ($request->callePropietario !== null && $request->callePropietario !== '') {
        //     $validator = Validator::make($request->all(), [
        //         'numeroPropietario' => 'required|string|max:10',
        //         'idLocalidadPropietario' => 'required',
        //     ], [
        //         'numeroPropietario.required' => '<li> El NÚMERO del PROPIETARIO es obligatorio si se proporciona una CALLE del PROPIETARIO. </li>',
        //         'numeroPropietario.string' => '<li> El NÚMERO del PROPIETARIO debe ser una cadena de texto válida. </li>',
        //         'numeroPropietario.max' => '<li> El NÚMERO del PROPIETARIO no puede tener más de 10 caracteres. </li>',
        //         'idLocalidadPropietario.required' => '<li> La LOCALIDAD del PROPIETARIO es obligatoria si se proporciona una CALLE del PROPIETARIO. </li>',                
        //     ]);

        //     if ($validator->fails()) 
        //     {
        //         $this->activeTab = 'propietario';

        //         return $validator; // Envía los errores a la vista
        //     }
        // }

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
            // 'callePropietario' => 'nullable|string|max:60',
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

            if ($request->tipoPropiedad === 2)
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
        }
        else  //Si no existe la persona con el CURP la crea
        {
            $personaPropietario = Persona::create([
                'curp' => trim(mb_strtoupper($request->curpPropietario)),
                'nombre' => trim(mb_strtoupper($request->nomPropietario)),
                'apellidos' => trim(mb_strtoupper($request->apePropietario)),
            ]);
        }

        return $personaPropietario;
    }

    public function regresaPropietario(Request $request, $personaPropietario)
    {
        $propietario = Solicitante::where('id_persona', $personaPropietario->id)->first();
        if ($propietario) //Si ya existe
        {
            if ($propietario->editable) //Si se pueden editar los datos del propietario
            {   //Si hay cambios entonces actualiza el campo correspondiente
                if ($propietario->telefono != trim($request->telefonoPropietario))
                {
                    $propietario->telefono = trim($request->telefonoPropietario);
                }
                if ($propietario->email != trim(mb_strtolower($request->emailPropietario)))
                {
                    $propietario->email = $request->emailPropietario !== null ? trim(mb_strtolower($request->emailPropietario)) : null;
                }
                $propietario->save();
            }
        }
        else  //Si no existe el propietario lo crea
        {
            $propietario = Solicitante::create([
                'id_persona' => $personaPropietario->id,
                'telefono' => trim($request->telefonoPropietario),
                'email' => $request->emailPropietario !== null ? trim(mb_strtolower($request->emailPropietario)) : null,
            ]);
        }

        return $propietario;
    }

    public function regresaPersonaSolicitante(Request $request)
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
        }
        else  //Si no existe la persona con el CURP la crea
        {
            $personaSolicitante = Persona::create([
                'curp' => trim(mb_strtoupper($request->curpSolicitante)),
                'nombre' => trim(mb_strtoupper($request->nomSolicitante)),
                'apellidos' => trim(mb_strtoupper($request->apeSolicitante)),
            ]);
        }

        return $personaSolicitante;
    } 
    
    public function regresaSolicitante(Request $request, $personaSolicitante)
    {
        $solicitante = Solicitante::where('id_persona', $personaSolicitante->id)->first();
        if ($solicitante) //Si ya existe
        {
            if ($solicitante->editable) //Si se pueden editar los datos del solicitante
            {   //Si hay cambios entonces actualiza el campo correspondiente
                if ($solicitante->telefono != trim($request->telefonoSolicitante))
                {
                    $solicitante->telefono = trim($request->telefonoSolicitante);
                }
                if ($solicitante->email != trim(mb_strtolower($request->emailSolicitante)))
                {
                    $solicitante->email = $request->emailSolicitante !== null ? trim(mb_strtolower($request->emailSolicitante)) : null;
                }
                $solicitante->save();
            }
        }
        else  //Si no existe el solicitante lo crea
        {
            $solicitante = Solicitante::create([
                'id_persona' => $personaSolicitante->id,
                'telefono' => trim($request->telefonoSolicitante),
                'email' => $request->emailSolicitante !== null ? trim(mb_strtolower($request->emailSolicitante)) : null,
            ]);
        }

        return $solicitante;
    }

    public function store(Request $request)  //Guarda por primera vez la solicitud
    {
        $errores = $this->validaSolicitud($request);

        if ($errores) {
            return back()->withErrors($errores)->with('activeTab', $this->activeTab);
        }

        $tramitesSeleccionados = $request->tramitesSeleccionados;
        
        try {
            DB::beginTransaction(); // Inicia la transacción

            $personaPropietario = $this->regresaPersonaPropietario($request);
            $propietario = $this->regresaPropietario($request, $personaPropietario);
            
            $esSolicitante = filter_var($request->esSolicitante, FILTER_VALIDATE_BOOLEAN);
            if ($esSolicitante) //Si el solicitante es el propietario
            {
                $solicitante = $propietario;
            }
            else
            {   //Busca a la persona con el CURP
                $personaSolicitante = $this->regresaPersonaSolicitante($request);
                $solicitante = $this->regresaSolicitante($request, $personaSolicitante);                
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


            $solicitud = Solicitud::create([
                'id_solicitante' => $solicitante->id,
                'id_propietario' => $propietario->id,
                'id_propiedad' => $propiedad->id,
                'id_destino_obra' => $request->idDestinoObra,
                'id_estatus' => $request->idEstatusSolicitud,
                'fecha_ingreso' => $request->fecha_ingreso,
            ]);

            $folioDigital = Str::random(25);

            // Después de crear la solicitud, asigna el ID al folio y guarda los cambios
            $solicitud->folio = $solicitud->id;
            $solicitud->folio_digital = $folioDigital;
            $solicitud->save();

            if (!empty($tramitesSeleccionados)) {
                foreach ($tramitesSeleccionados as $tramiteId) {
                    SolicitudTramite::create([
                        'id_solicitud' => $solicitud->id,
                        'id_tramite' => $tramiteId,
                    ]);
                }
            }

            DB::commit(); // Confirma la transacción si todo salió bien
        
            return back()->with('success', 'Solicitud N° ' . str_pad($solicitud->id, 4, '0', STR_PAD_LEFT) . ' creada exitosamente')
             ->with('solicitudes', Solicitud::all());
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte la transacción si ocurre un error
            return response()->json(['error' => 'Hubo un error al crear el usuario: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $errores = $this->validaSolicitud($request);

        if ($errores) 
        {
            return back()->withErrors($errores)->with('activeTab', $this->activeTab);
        }

        $nuevoSolicitante = filter_var($request->input('nuevoSolicitante'), FILTER_VALIDATE_BOOLEAN);

        DB::beginTransaction();
        try 
        {
            $this->validaSolicitud($request);

            $solicitud = Solicitud::findOrFail($id);

            $personaPropietario = $this->regresaPersonaPropietario($request);
            $propietario = $this->regresaPropietario($request, $personaPropietario);
            
            $esSolicitante = filter_var($request->esSolicitante, FILTER_VALIDATE_BOOLEAN);
            if ($esSolicitante) //Si el solicitante es el propietario
            {
                $solicitante = $propietario;
            }
            else
            {   //Busca a la persona con el CURP
                $personaSolicitante = $this->regresaPersonaSolicitante($request);
                $solicitante = $this->regresaSolicitante($request, $personaSolicitante);                
            }            

            $solicitud->id_solicitante = $solicitante->id;
            $solicitud->id_propietario = $propietario->id; 
            $solicitud->id_destino_obra = $request->idDestinoObra;
            $solicitud->id_estatus = $request->idEstatusSolicitud;
            $solicitud->fecha_ingreso = $request->fecha_ingreso;

            if ($request->idPropiedadSolicitud)
            {
                $propiedad = Propiedad::findOrFail($request->idPropiedadSolicitud);
                $solicitud->id_propiedad = $request->idPropiedadSolicitud;

                $propiedad->clave_catastral = trim($request->claveCatastral);
                $propiedad->id_tipo = $request->tipoPropiedad;
                $propiedad->superficie = $request->superficiePropiedad;
                $propiedad->superficie_construccion = $request->superficieConstruccionPropiedad;
                $propiedad->calle = trim(mb_strtoupper($request->callePropiedad));
                $propiedad->numero = trim(mb_strtoupper($request->numeroPropiedad));
                $propiedad->id_colonia = $request->idColoniaPropiedad;
                $propiedad->id_localidad = $request->idLocalidadPropiedad;
            }
            else
            {
                $propiedad = Propiedad::create([
                    'clave_catastral' => trim($request->claveCatastral),
                    'calle' => trim(mb_strtoupper($request->callePropiedad)),
                    'numero' => trim(mb_strtoupper($request->numeroPropiedad)),
                    'id_colonia' => $request->idColoniaPropiedad,
                    'id_localidad' => $request->idLocalidadPropiedad,
                    'superficie' => $request->superficiePropiedad,
                    'superficie_construccion' => $request->superficieConstruccionPropiedad,
                    'img_croquis' => $request->imgCroquisPropiedad
                ]);
                $solicitud->id_propiedad = $propiedad->id;
            }

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
                if ($request->idPersonaPropietario)
                {
                    $personaPropietario = Persona::findOrFail($request->idPersonaPropietario);
                    if ($personaPropietario)  //Se pone como NO EDITABLE la PERSONA del PROPIETARIO
                    {
                        $personaPropietario->editable = 0;
                        $personaPropietario->save();
                    }
                }

                if ($request->idPersonaSolicitante)
                {
                    $personaSolicitante = Persona::findOrFail($request->idPersonaSolicitante);
                    if ($personaSolicitante) //Se pone como NO EDITABLE la PERSONA del SOLICITANTE
                    {
                        $personaSolicitante->editable = 0;
                        $personaSolicitante->save();
                    }
                }

                if ($request->idPropietarioSolicitud)
                {
                    $propietarioC = Solicitante::
                                   where('id', $request->idPropietarioSolicitud)->
                                   where('telefono', trim($request->telefonoPropietario))->
                                   where('email', trim($request->emailPropietario))->
                                   first();

                    if ($propietarioC)
                    {
                        $propietarioC->editable = 0;
                        $propietarioC->save();
                    }
                    else
                    {
                        $propietarioC = Solicitante::findOrFail($request->idPropietarioSolicitud);
                        if ($propietarioC)
                        {
                            $propietarioC->activo = 0;
                            $propietarioC->save();
                        }

                        $propietarioActivo = Solicitante::create([
                            'id_persona' => $request->idPropietarioSolicitud,
                            'telefono' => trim($request->telefonoPropietario),
                            'email' => $request->emailPropietario !== null ? trim(mb_strtolower($request->emailPropietario)) : null,
                        ]);

                        $solicitud->id_propietario = $propietarioActivo->id;
                    }
                }

                if ($request->idSolicitanteSolicitud  && ($request->idPropietarioSolicitud != $request->idSolicitanteSolicitud))
                {
                    $solicitanteC = Solicitante::findOrFail($request->idSolicitanteSolicitud);

                    if ($solicitanteC)
                    {
                        $solicitanteC->editable = 0;
                        $solicitanteC->save();
                    }
                }



                // if ($request->idPropiedadSolicitud)
                // {
                //     $propiedad = Propiedad::findOrFail($request->idPropiedadSolicitud);
                //     $propiedad->activo = 0;
                //     $propiedad->save();
                // }

            }

            $propiedad->save();
            $propietario->save();
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
