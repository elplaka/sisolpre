<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Propiedad;
use App\Models\TipoPropiedad;
use App\Models\Tramite;
use App\Models\Persona;
use App\Models\Contacto;
use App\Models\DomicilioNotificacion;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class PropiedadController extends Controller
{

    public function store(Request $request)
    {
        try {
            $rules = [
                'id_tipo' => 'nullable|integer',
                'calle' => 'required|string|max:80',
                'numero' => 'nullable|string|max:8',
                'id_colonia' => 'required|integer',
                'id_localidad' => 'required|integer',
                'codigo_postal' => 'required|numeric',
                'referencias_ubicacion' => 'required|string',
                'superficie' => 'nullable|numeric|min:0',
                'superficie_construccion' => 'nullable|numeric|min:0',
                'img_croquis' => 'nullable|string|max:35',
                'coordenada_utm_x' => 'nullable|numeric',
                'coordenada_utm_y' => 'nullable|numeric',
                'id_contacto' => 'nullable|integer',
                'id_persona' => 'nullable|integer',

                'curp_propietario' => [
                    'required',
                    'string',
                    'size:18',
                    'regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9A-Z]{1}[0-9]{1}$/'
                ],

                'nombre_propietario' => 'required|string|max:30',
                'apellidos_propietario' => 'required|string|max:40',
                'email_propietario' => 'nullable|email|max:60',
                'telefono_propietario' => 'nullable|numeric|digits:10',
                'domicilio_notificacion' => 'nullable|string|max:100'
            ];

            $messages = [
                // Ubicación
                'id_tipo.integer'                 => 'El tipo de propiedad seleccionado es inválido.',
                'calle.required'                  => 'La vialidad es obligatoria.',
                'calle.max'                       => 'La vialidad no puede superar los 80 caracteres.',
                'numero.max'                      => 'El número no puede superar los 8 caracteres.',
                'id_colonia.integer'              => 'La colonia seleccionada es inválida.',
                'id_localidad.required'           => 'La localidad es obligatoria.',
                'id_localidad.integer'            => 'La localidad seleccionada es inválida.',
                'codigo_postal.required'          => 'El código postal es obligatorio.',
                'codigo_postal.numeric'           => 'El código postal debe ser numérico.',

                //Información técnica
                'superficie.numeric'              => 'La superficie del terreno debe ser un número.',
                'superficie.min'                  => 'La superficie del terreno no puede ser menor a 0.',
                'superficie_construccion.numeric' => 'La superficie de construcción debe ser un número.',
                'superficie_construccion.min'     => 'La superficie de construcción no puede ser menor a 0.',
                'coordenada_utm_x.numeric'        => 'La coordenada UTM X debe ser un valor numérico.',
                'coordenada_utm_y.numeric'        => 'La coordenada UTM Y debe ser un valor numérico.',

                // Propietario
                'curp_propietario.required' => 'La CURP del propietario es obligatoria.',
                'curp_propietario.size'     => 'La CURP del propietario debe tener exactamente 18 caracteres.',
                'curp_propietario.regex'    => 'El formato de la CURP es inválido.',
                'nombre_propietario.required'    => 'El nombre del propietario es obligatorio.',
                'nombre_propietario.max'         => 'El nombre del propietario no puede superar los 30 caracteres.',
                'apellidos_propietario.required' => 'Los apellidos del propietario son obligatorios.',
                'apellidos_propietario.max'      => 'Los apellidos del propietario no pueden superar los 40 caracteres.',
                'email_propietario.email'        => 'El correo electrónico del propietario debe ser una dirección válida.',
                'email_propietario.max'          => 'El correo electrónico no puede superar los 60 caracteres.',
                'telefono_propietario.numeric'   => 'El teléfono del propietario debe contener únicamente números.',
                'telefono_propietario.digits'    => 'El teléfono del propietario debe tener exactamente 10 dígitos.',
                'domicilio_notificacion.max'     => 'El domicilio de notificación no puede superar los 100 caracteres.',

                //Croquis
                'img_croquis.max'                 => 'El nombre de la imagen del croquis no puede superar los 35 caracteres.',
            ];

            // Instancia final del validador pasándole las reglas y los mensajes
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                $curp = $request->input('curp_propietario');
                $genero = (strlen($curp) >= 11) ? strtoupper($curp[10]) : 'O';
                $terminacion = ($genero === 'M') ? 'A' : 'O'; // PROPIETARIA o PROPIETARIO


                $errors = $validator->errors();

                foreach ($errors->keys() as $field) {
                    if (str_contains($field, 'propietario')) {
                        $mensajesDelCampo = $errors->get($field);
                        $errors->forget($field);

                        foreach ($mensajesDelCampo as $mensaje) {
                            // 1. Definimos las sustituciones basadas en el género
                            if ($terminacion === 'A') { // PROPIETARIA
                                $sustituciones = [
                                    'del propietario' => 'de la propietaria',
                                    'propietario'     => 'propietaria'
                                ];
                            } else { // PROPIETARIO
                                $sustituciones = [
                                    'de la propietaria' => 'del propietario', // Por si acaso
                                    'propietario'       => 'propietario'
                                ];
                            }

                            // 2. Aplicamos las sustituciones en orden (de la más específica a la general)
                            $nuevoMensaje = $mensaje;
                            foreach ($sustituciones as $busqueda => $reemplazo) {
                                $nuevoMensaje = str_ireplace($busqueda, $reemplazo, $nuevoMensaje);
                            }

                            $nuevoMensaje = ucfirst($nuevoMensaje);
                            $errors->add($field, $nuevoMensaje);
                        }
                    }
                }

                return response()->json($errors, 422);
            }

            $propiedadExistente = Propiedad::where('clave_catastral', $request->clave_catastral)
                ->where('activa', 1)
                ->first();

            // 2. Si existe, y el usuario NO ha mandado el flag de 'confirmado' desde el front
            if ($propiedadExistente && !$request->boolean('confirmado')) {

                // Aquí puedes añadir lógica extra si quieres ser más específico 
                // (ej: solo pedir confirmación si realmente hay cambios, 
                // pero por seguridad, pedirla si ya existe una activa es un estándar).

                return response()->json(['requiere_confirmacion' => true], 200);
            }

            $contactoActual = Contacto::findOrFail($request->input('id_contacto'));
            $personaActual = $contactoActual->persona;
            $domicilioNotificacionActual = $contactoActual->domicilio_notificacion?->direccion;

            // 3. Preparar y limpiar datos del Request
            $nuevosDatosPersona = [
                'curp'      => mb_strtoupper(trim($request->input('curp_propietario')), 'UTF-8'),
                'nombre'    => mb_strtoupper(trim($request->input('nombre_propietario')), 'UTF-8'),
                'apellidos' => mb_strtoupper(trim($request->input('apellidos_propietario')), 'UTF-8'),
            ];

            $nuevosDatosContacto = [
                'email'    => trim(strtolower($request->input('email_propietario'))),
                'telefono' => trim($request->input('telefono_propietario')),
            ];

            // 4. Cargar en memoria para evaluar cambios con isDirty()
            $personaActual->fill($nuevosDatosPersona);
            $contactoActual->fill($nuevosDatosContacto);

            $cambioPersona = $personaActual->isDirty(['curp', 'nombre', 'apellidos']);
            $cambioContacto = $contactoActual->isDirty(['email', 'telefono']);

            // 1. Limpiamos y formateamos el texto del request para una comparación justa
            $domicilioNotificacionNueva = trim(mb_strtoupper($request->input('domicilio_notificacion'), 'UTF-8'));

            $nuevosDatosDomicilioNotificacion = [
                'direccion' => $domicilioNotificacionNueva
            ];

            $cambioDomicilioNotificacion = false;

            // 2. Evaluamos según si ya existía un domicilio previo o no
            if ($domicilioNotificacionActual) {
                // 🚀 LA CLAVE: Comparamos TEXTO contra TEXTO apuntando al campo 'direccion'
                if ($domicilioNotificacionActual !== $domicilioNotificacionNueva) {
                    $cambioDomicilioNotificacion = true;
                }
            } else {
                // Si antes era null y ahora el usuario escribió algo, sí hay un cambio
                $cambioDomicilioNotificacion = !empty($direccionNotificacionNueva);
            }

            DB::beginTransaction();

            $idContactoFinal = $contactoActual->id;

            // 🚀 Si algo cambió, abrimos transacción
            if ($cambioPersona || $cambioContacto || $cambioDomicilioNotificacion) {
                // Generamos la réplica limpia desde el estado original de la base de datos
                $nuevoContacto = $contactoActual->replicate();

                if ($cambioPersona) {
                    // Caso A: Cambió la persona (Nueva persona + nuevo contacto con nuevos datos)
                    $nuevaPersona = Persona::create($nuevosDatosPersona);

                    $nuevoContacto->id_persona = $nuevaPersona->id;
                } else {
                    // Caso B: Misma persona, solo cambia email/teléfono (Aseguramos que conserve la ID de la persona actual)
                    $nuevoContacto->id_persona = $contactoActual->getOriginal('id_persona');
                }

                if ($cambioDomicilioNotificacion) {
                    // Creamos un nuevo domicilio independiente con los datos actuales
                    $nuevoDomicilio = DomicilioNotificacion::create($nuevosDatosDomicilioNotificacion);
                    $nuevoContacto->id_domicilio = $nuevoDomicilio->id;
                } else {
                    // Mantiene el mismo domicilio que ya tenía
                    $nuevoContacto->id_domicilio = $contactoActual->getOriginal('id_domicilio');
                }

                // Para cualquiera de los dos casos, el nuevo contacto se queda con la información del Request
                $nuevoContacto->email = $nuevosDatosContacto['email'];
                $nuevoContacto->telefono = $nuevosDatosContacto['telefono'];
                $nuevoContacto->save();

                $idContactoFinal = $nuevoContacto->id;
            }

            $excepto = ['id', 'activa', 'editable', 'fecha_aceptacion', 'created_at', 'updated_at'];
            $camposTabla = Schema::getColumnListing('propiedades');
            $camposPermitidos = array_diff($camposTabla, $excepto);

            $datos = $request->only($camposPermitidos);

            $datos = array_map(function ($valor) {
                if (is_string($valor)) {
                    return mb_strtoupper(trim($valor), 'UTF-8');
                }
                return $valor;
            }, $datos);

            $datos['id_contacto'] = $idContactoFinal;

            $propiedadActiva = Propiedad::where('clave_catastral', $datos['clave_catastral'])
                ->where('activa', 1)
                ->latest('id')
                ->first();

            if ($propiedadActiva) {
                $datosActuales = $propiedadActiva->only($camposPermitidos);

                $datosActuales = array_filter($datosActuales, fn($v) => $v !== null);
                $datosNuevos = array_filter($datos, fn($v) => $v !== null && $v !== '');

                $propiedadActiva->editable = 0;
                $propiedadActiva->fecha_aceptacion = now();
                $propiedadActiva->save();

                if ($datosActuales == $datosNuevos) {
                    DB::rollBack();
                    return redirect()->back()->with('info', 'No hay cambios en los datos respecto al último registro.');
                }
            }

            Propiedad::where('clave_catastral', $datos['clave_catastral'])
                ->update(['activa' => 0]);  //Se ponen inactivas todas las propiedades con la clave catastral

            $nuevaPropiedad = Propiedad::create($datos);

            $tramite = Tramite::find($request->input('id_tramite'));

            if ($tramite) {
                $tramite->update(['id_propiedad' => $nuevaPropiedad->id]);
                $constanciaNumeroOficial = $tramite->constanciaNumeroOficial()->first();
                if ($constanciaNumeroOficial) {
                    $constanciaNumeroOficial->update([
                        'id_propiedad' => $nuevaPropiedad->id
                    ]);
                }

                $tramite->unsetRelation('constanciaNumeroOficial');
                $tramite->refresh();
            }

            DB::commit();

            return redirect()->route('tramites.edit', [
                'tipo_tramite'    => 'constancia-de-numero-oficial', // Parámetro de la ruta
                'id_tramite'      => $request->input('id_tramite'),                              // Query parameter 1
                'desde_propiedad' => $request->input('desde_propiedad')                          // Query parameter 2
            ]);
        } catch (ValidationException $e) {

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            // 💡 En lugar de un dd(), mandamos el error de regreso de manera controlada
            return redirect()->back()->with('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    public function obtenerPropiedades()
    {
        $propiedadesQuery = Propiedad::with([
            'colonia',
            'localidad',
            'tipo',
            'contacto',
            'contacto.persona',
            'contacto.domicilio_notificacion'
        ])
            ->where('activa', true)
            ->where('id', '>', 0);

        // dd($propiedadesQuery->get());

        return [
            'query' => $propiedadesQuery,
        ];
    }

    private function prepararVistaPropiedades(array $filtros): array
    {
        $propiedadesQuery = $this->obtenerPropiedades();
        $propiedades = $propiedadesQuery['query']->paginate(8);

        $tiposPropiedad = TipoPropiedad::where('activo', 1)->get();


        // $estatus = EstatusTramite::all();

        return [
            'userAuth' => Auth::user(),
            'propiedades' => $propiedades,
            'tiposPropiedad' => $tiposPropiedad
        ];
    }

    public function index(Request $request)
    {
        $page = $request->input('page', 1);

        $filtros = [
            'page' => $page,
        ];

        $props = $this->prepararVistaPropiedades($filtros);

        $props['filters'] = $filtros;

        return Inertia::render('Propiedades/Index', $props);
    }

    /**
     * Obtiene el historial de propiedad por clave catastral
     * 
     * @param string $claveCatastral
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */

    public function getHistorialPropiedad($claveCatastral)
    {
        $registros = Propiedad::with(['contacto.persona', 'colonia', 'localidad', 'tipo'])
            ->where('clave_catastral', $claveCatastral)
            ->orderBy('created_at', 'asc')
            ->get();

        $historialFormateado = [];

        for ($i = 0; $i < count($registros); $i++) {
            $actual = $registros[$i];
            $anterior = $i > 0 ? $registros[$i - 1] : null;
            $cambios = [];

            // 1. CAMPOS DE LA PROPIEDAD (Los que ya tenías)
            $camposPropiedad = [
                'clave_catastral',
                'superficie',
                'calle',
                'numero',
                'codigo_postal',
                'referencias_ubicacion',
                'superficie',
                'superficie_construccion',
                'img_croquis',
                'coordenada_utm_x',
                'coordenada_utm_y',
            ];
            foreach ($camposPropiedad as $campo) {
                if ($anterior && $actual->$campo != $anterior->$campo) {
                    $cambios[$campo] = ['anterior' => $anterior->$campo, 'nuevo' => $actual->$campo];
                }
            }

            // Solo comparamos si existe un registro anterior
            if ($anterior) {
                $nombreActual = $actual->colonia?->nombre;
                $nombreAnterior = $anterior->colonia?->nombre;

                if ($nombreActual != $nombreAnterior) {
                    $cambios["id_colonia"] = [
                        'anterior' => $nombreAnterior ?? 'Nulo',
                        'nuevo' => $nombreActual ?? 'Nulo'
                    ];
                }
            }

            if ($anterior) {
                $nombreActual = $actual->localidad?->nombre;
                $nombreAnterior = $anterior->localidad?->nombre;

                if ($nombreActual != $nombreAnterior) {
                    $cambios["id_localidad"] = [
                        'anterior' => $nombreAnterior ?? 'Nulo',
                        'nuevo' => $nombreActual ?? 'Nulo'
                    ];
                }
            }

            // 2. CAMPOS DEL CONTACTO (Relación directa)
            if ($anterior && $actual->contacto && $anterior->contacto) {
                $camposContacto = ['telefono', 'email', 'id_domicilio'];
                foreach ($camposContacto as $campo) {
                    if ($actual->contacto->$campo != $anterior->contacto->$campo) {
                        $cambios["Contacto: " . $campo] = [
                            'anterior' => $anterior->contacto->$campo ?? 'Nulo',
                            'nuevo' => $actual->contacto->$campo ?? 'Nulo'
                        ];
                    }
                }
            }

            // 3. CAMPOS DE LA PERSONA (Relación anidada)
            if ($anterior && $actual->contacto?->persona && $anterior->contacto?->persona) {
                $perActual = $actual->contacto->persona;
                $perAnterior = $anterior->contacto->persona;

                $camposPersona = ['curp', 'nombre', 'apellidos'];
                foreach ($camposPersona as $campo) {
                    if ($perActual->$campo != $perAnterior->$campo) {
                        $cambios["Titular: " . $campo] = [
                            'anterior' => $perAnterior->$campo,
                            'nuevo' => $perActual->$campo
                        ];
                    }
                }
            }

            // Solo agregamos al historial si hubo cambios o es el primer registro
            if (!empty($cambios) || !$anterior) {
                $historialFormateado[] = [
                    'usuario_nombre' => $actual->contacto?->persona->nombre ?? 'Sistema',
                    'created_at' => $actual->created_at,
                    'descripcion' => $anterior ? 'Actualización de Registro' : 'Registro Inicial',
                    'cambios' => $cambios
                ];
            }
        }

        return response()->json(array_reverse($historialFormateado));
    }

    public function edit(Request $request)
    {
        $propiedad = Propiedad::findOrFail($request->input('id_propiedad'));
        $tramite = Tramite::findOrFail($request->input('id_tramite'));

        $propiedad->load(['tipo', 'colonia', 'localidad', 'contacto', 'contacto.persona', 'contacto.domicilio_notificacion']);

        $tramite->load(['tipoTramite', 'solicitud']);

        $slugTipoTramite = $tramite->tipoTramite->slug;

        $tramiteData = $request->input('tramite_data');

        return Inertia::render('Propiedades/Edit', [
            'propiedad' => $propiedad, // Va al :selectedPropiedad
            'tiposPropiedad' => TipoPropiedad::all(), // O como obtengas tus tipos
            'tramite' => $tramite,
            'desdeTramite' => $request->boolean('desde_tramite'),
            'tipoTramite' => $slugTipoTramite,
            'campoEspecifico' => $request->input('campo_especifico'),
            'userAuth' => Auth::user(),
            'tramiteData' => $tramiteData
        ]);
    }
}
