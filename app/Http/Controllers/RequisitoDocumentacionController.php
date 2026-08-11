<?php

namespace App\Http\Controllers;

use App\Models\RequisitoDocumentacion;
use App\Models\CatalogoTramite;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

const PAG = 10;

class RequisitoDocumentacionController extends Controller
{
    public function obtenerRequisitos($nomRequisitosQuery, $nomTramitesQuery, $obligatorioQuery, $activoQuery, $agruparTramites): array
    {
        if ($agruparTramites) {
            $applyRequisitosFilter = function ($query) use ($nomRequisitosQuery) {
                if (!is_null($nomRequisitosQuery) && !empty($nomRequisitosQuery)) {
                    $query->whereIn('requisitos_documentacion.id', $nomRequisitosQuery);
                }
                return $query;
            };

            $requisitosQuery = RequisitoDocumentacion::orderBy('nombre')
                ->with('tramites');

            $requisitosQuery = $applyRequisitosFilter($requisitosQuery);

            if (!is_null($nomTramitesQuery) && !empty($nomTramitesQuery)) {
                $requisitosQuery->whereHas('tramites', function ($subQuery) use ($nomTramitesQuery) {
                    $subQuery->whereIn('catalogo_tramites_requisitos.id_tramite_catalogo', $nomTramitesQuery);
                });
            }

            if (!is_null($activoQuery) && $activoQuery !== '') {
                if ($activoQuery) {
                    $requisitosQuery->where('activo', true);
                } elseif (!$activoQuery) {
                    $requisitosQuery->where('activo', false);
                }
            }
        } else {
            // 1. Consulta Base con JOINS (Query Builder)
            $requisitosQuery = RequisitoDocumentacion::query()
                ->leftjoin('catalogo_tramites_requisitos as ctr', 'requisitos_documentacion.id', '=', 'ctr.id_requisito')
                ->leftjoin('catalogo_tramites as ct', 'ctr.id_tramite_catalogo', '=', 'ct.id')
                ->select(
                    'requisitos_documentacion.id as id',
                    'requisitos_documentacion.nombre as requisito_nombre',
                    'requisitos_documentacion.nombre_corto as nombre_corto',
                    'requisitos_documentacion.editable as requisito_editable',
                    'requisitos_documentacion.activo as activo',
                    'ct.id as id_tramite',
                    'ct.nombre as tramite_nombre',
                    'ctr.id_tramite_catalogo as requisito_tramite_id_tramite',
                    'ctr.id_requisito as requisito_tramite_id_requisito',
                    'ctr.obligatorio',
                    'ctr.activo as requisito_activo',
                    'ctr.editable as requisito_tramite_editable'
                )
                ->orderBy('ct.nombre', 'asc')
                ->orderBy('requisitos_documentacion.nombre', 'asc');

            if (!is_null($nomRequisitosQuery) && !empty($nomRequisitosQuery)) {
                $requisitosQuery->whereIn('requisitos_documentacion.id', $nomRequisitosQuery);
            }

            if (!is_null($nomTramitesQuery) && !empty($nomTramitesQuery)) {
                $requisitosQuery->whereIn('ctr.id_tramite_catalogo', $nomTramitesQuery);
            }
            if (!is_null($obligatorioQuery) && $obligatorioQuery !== '') {
                if ($obligatorioQuery) {
                    $requisitosQuery->where('ctr.obligatorio', true);
                } elseif (!$obligatorioQuery) {
                    $requisitosQuery->where('ctr.obligatorio', false);
                }
            }
            if (!is_null($activoQuery) && $activoQuery !== '') {
                if ($activoQuery) {
                    $requisitosQuery->where('ctr.activo', true);
                } elseif (!$activoQuery) {
                    $requisitosQuery->where('ctr.activo', false);
                }
            }
        }

        return [
            'query' => $requisitosQuery,
        ];
    }

    public function getRequisitosNoAsignadosTramite($idTramite)
    {
        $requisitosNoAsignados = RequisitoDocumentacion::whereDoesntHave('tramites', function (Builder $query) use ($idTramite) {
            $query->where('id_tramite_catalogo', $idTramite);
        })->orderBy('nombre')->get();

        return response()->json([
            'requisitosNoAsignados' => $requisitosNoAsignados,
        ]);
    }

    private function prepararVistaRequisitos(array $filtros): array
    {
        $colores = [
            // 15 COLORES BASE (Orden Cromático)
            '#000000', // 1. Negro
            '#800000', // 2. Guinda
            '#FF0000', // 3. Rojo
            '#FF8C00', // 4. Naranja
            '#964B00', // 5. Café
            '#d2d208', // 6. Amarillo
            '#008000', // 7. Verde
            '#00A0A0', // 8. Azul-Verde
            '#5ee200', // 9. Cyan
            '#000080', // 10. Azul Marino
            '#4B0082', // 11. Azul-Violeta
            '#8A2BE2', // 12. Violeta
            '#800080', // 13. Morado
            '#FF69B4', // 14. Rosa
            '#5c5c5c', // 15. Gris

            // 15 TONOS PASTEL (Versiones claras de los anteriores)
            '#6796c7', // 16. Pastel de Negro (Gris Claro)
            '#906969', // 17. Pastel de Guinda (Rosa Pálido)
            '#FF9999', // 18. Pastel de Rojo
            '#d8a65f', // 19. Pastel de Naranja
            '#9c8c81', // 20. Pastel de Café (Marrón Claro/Beige)
            '#bcbc73', // 21. Pastel de Amarillo
            '#5e805e', // 22. Pastel de Verde
            '#5e80b3', // 23. Pastel de Azul-Verde
            '#62ecec', // 24. Pastel de Cyan
            '#7e7eb3', // 25. Pastel de Azul Marino
            '#a67cb9', // 26. Pastel de Azul-Violeta (Lavanda)
            '#675b7c', // 27. Pastel de Violeta
            '#995099', // 28. Pastel de Morado (Lila)
            '#eb88ba', // 29. Pastel de Rosa
            '#2a0d0d', // 30. Pastel de Gris (Muy Claro)
        ];
        $totalColores = count($colores);

        $requisitosQuery = $this->obtenerRequisitos(
            $filtros['nomRequisitosQuery'],
            $filtros['nomTramitesQuery'],
            $filtros['obligatorioQuery'],
            $filtros['activoQuery'],
            $filtros['agruparTramites'],
        );

        if ($filtros['agruparTramites']) {
            $requisitos = $requisitosQuery['query']->paginate(PAG);

            // 3. Mapear los colores a la colección de la página actual.
            // Esto debe hacerse después de paginate().
            $requisitos->getCollection()->each(function ($requisito) use ($colores, $totalColores) {

                // Iteramos sobre la relación de trámites cargada
                $requisito->tramites->each(function ($tramite) use ($colores, $totalColores) {
                    // Asignamos un color basado en el ID del trámite, ciclando con MÓDULO.
                    $indiceColor = ($tramite->id % $totalColores);
                    $tramite->color = $colores[$indiceColor];
                });
            });
        } else {
            $requisitos = $requisitosQuery['query']->paginate(PAG);

            // Mapeamos los colores a la colección de la página actual.
            $requisitos->getCollection()->each(function ($requisito) use ($colores, $totalColores) {

                // Verifica si hay un ID de trámite (puede ser NULL por el leftJoin)
                if ($requisito->id_tramite !== null) {
                    $indiceColor = ($requisito->id_tramite % $totalColores);
                    $requisito->color = $colores[$indiceColor];
                } else {
                    // Si no hay id_tramite (requisito sin trámites), asignamos un color neutro o NULL
                    $requisito->color = null; // O un color como '#D9D9D9' (Gris Claro)
                }
            });
        }

        $requisitosSelect = RequisitoDocumentacion::orderBy('nombre')->get();
        $requisitoIds = $requisitosSelect->pluck('id')->toArray();

        $tramitesSelect = CatalogoTramite::whereHas('requisitos', function ($query) use ($requisitoIds) {
            $query->whereIn('id_requisito', $requisitoIds);
        })->orderBy('nombre')
            ->get();

        $tramitesSelect->each(function ($tramite) use ($colores, $totalColores) {
            $indiceColor = ($tramite->id % $totalColores);

            // Asignar el color al atributo 'color' del modelo
            $tramite->color = $colores[$indiceColor];
        });

        $tramites = CatalogoTramite::with('requisitos')->where('activo', 1)->orderBy('nombre')->get();

        $tramites->each(function ($tramite) use ($colores, $totalColores) {
            $indiceColor = ($tramite->id % $totalColores);

            // Asignar el color al atributo 'color' del modelo
            $tramite->color = $colores[$indiceColor];
        });

        return [
            'userAuth' => Auth::user(),
            'requisitos' => $requisitos,
            'requisitosSelect' => $requisitosSelect,
            'tramitesSelect' => $tramitesSelect,
            'tramites' => $tramites,
            'agruparTramites' => $filtros['agruparTramites'],
            'paginaActual' => $filtros['page'],
        ];
    }

    public function store_assignment_tramite(Request $request)
    {
        if ($request->requisitoObligatorio === 'null') {
            $request->merge(['requisitoObligatorio' => null]);
        }

        try {
            $messages = [
                'idTramite.required' => 'Debes seleccionar el <b> Trámite </b> al que se asocia este requisito.',
                'idTramite.integer' => 'Por favor, seleccionar un <b> Trámite </b>.',
                'idTramite.exists' => 'El <b> Trámite </b> seleccionado no es válido o no existe.',

                'idRequisito.unique' => 'El <b> Requisito </b> ya está asignado a este <b> Trámite </b>.',
                'idRequisito.required' => 'Debes seleccionar el <b> Requisito </b> al que se asocia este trámite.',
                'idRequisito.integer' => 'Por favor, seleccionar un <b> Requisito </b>.',
                'idRequisito.exists' => 'El <b> Requisito </b> seleccionado no es válido o no existe.',
            ];

            $validatedData = $request->validate([
                'idTramite' => 'required|integer|exists:catalogo_tramites,id',
                'idRequisito' => [
                    'required',
                    'integer',
                    'exists:requisitos_documentacion,id',

                    Rule::unique('catalogo_tramites_requisitos', 'id_requisito')
                        ->where(function ($query) use ($request) {
                            return $query->where('id_tramite_catalogo', $request->input('idTramite'));
                        }),
                ],
                'requisitoObligatorio' => 'required',
                'requisitoActivo' => 'required',
            ], $messages);

            $datosRequisitos = [
                'id_requisito' => $validatedData['idRequisito'],
                'id_tramite_catalogo' => $validatedData['idTramite'],
            ];

            $datosPivote = [
                'obligatorio' => $request->boolean('requisitoObligatorio'),
                'activo' => $request->boolean('requisitoActivo'),
            ];

            DB::beginTransaction();

            $tramite = CatalogoTramite::findOrFail($validatedData['idTramite']);

            $tramite->requisitos()->attach($validatedData['idRequisito'], $datosPivote);

            DB::commit();

            $filtros = $this->getFilteredParameters($request);

            return redirect()
                ->route('requisitos', $filtros)
                ->with('success', "Asignación creada exitosamente.");
        } catch (ValidationException $e) {
            $filtros = $this->getFilteredParameters($request);
            return redirect()
                ->route('requisitos', $filtros) // Redirige a la ruta específica con los filtros
                ->withErrors($e->errors())      // Adjunta los errores (Respuesta 422 para Inertia)
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            $filtros = $this->getFilteredParameters($request);
            Log::error("Fallo al crear requisito: " . $e->getMessage(), ['exception' => $e]);
            return redirect()
                ->route('requisitos', $filtros) // Redirige a la ruta específica con los filtros
                ->with('error', 'Ocurrió un error inesperado al crear el requisito. Por favor, avisa al administrador del sistema.');
        }
    }

    public function index(Request $request)
    {
        // Log::info('DEBUG INERCIA: Petición /requisitos recibida.');

        // dd($request->all());

        $filtros = $this->getFilteredParameters($request);

        $page = $request->input('page', 1);

        $props = $this->prepararVistaRequisitos($filtros);

        $lastPage = $props['requisitos']->lastPage();

        if ($lastPage < $page) {
            $filtros['page'] = $lastPage;
        }

        $props['filters'] = $filtros;

        return Inertia::render('Requisitos/Index', $props);
    }

    /**
     * CREATE: Muestra el formulario para crear un nuevo recurso. (GET /requisitos/create)
     */
    public function create()
    {
        // En un CRUD simple, esto podría renderizar un modal o una página.
        // Aquí redirigimos a Index para usar un modal dentro de esa página.
        return Inertia::render('Requisitos/Create');
    }

    private function getFilteredParameters(Request $request): array
    {
        // --- 1. Recuperar y sanear los campos de texto/array ---
        $nomRequisitosQuery = $request->input('nomRequisitosQuery', []);
        $nomTramitesQuery = $request->input('nomTramitesQuery', []);
        $requisitos = [];
        $tramites = [];

        // Lógica para manejar si la entrada es string o array (típico de inputs con Inertia/Vue)
        if (!is_array($nomRequisitosQuery)) {
            $requisitos = array_filter(is_null($nomRequisitosQuery) ? [] : (array)$nomRequisitosQuery);
        } else {
            $requisitos = $nomRequisitosQuery;
        }

        if (!is_array($nomTramitesQuery)) {
            $tramites = array_filter(is_null($nomTramitesQuery) ? [] : (array)$nomTramitesQuery);
        } else {
            $tramites = $nomTramitesQuery;
        }

        $valorRaw = $request->input('agruparTramites', 'true');
        $agruparTramites = filter_var($valorRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($agruparTramites === null) {
            $agruparTramites = true;
        }

        // dd($agruparTramites);

        $obligatorioQuery = $request->input('obligatorioQuery', '');
        $obligatorioQuery = match ($obligatorioQuery) {
            '1', 1, 'true', true => true,
            '0', 0, 'false', false => false,
            '', null, 'null' => null,
            default => null,
        };

        $activoQuery = $request->input('activoQuery', '');
        $activoQuery = match ($activoQuery) {
            '1', 1, 'true', true => true,
            '0', 0, 'false', false => false,
            '', null, 'null' => null,
            default => null,
        };

        $page = $request->input('page', 1);

        // --- 3. Construir y devolver el array de filtros ---
        return [
            'nomRequisitosQuery' => $requisitos,
            'nomTramitesQuery' => $tramites,
            'agruparTramites' => $agruparTramites,
            'obligatorioQuery' => $obligatorioQuery,
            'activoQuery' => $activoQuery,
            'page' => $page,
        ];
    }

    public function create_assignment(Request $request)
    {
        // --- PASO 1: Validación y Mensajes ---
        try {
            $messages = [
                'idTramite.required' => 'Debes seleccionar el <b> Trámite </b> al que se asocia este requisito.',
                'idTramite.integer' => 'Por favor, seleccionar un <b> Trámite </b>.',
                'idTramite.exists' => 'El <b> Trámite </b> seleccionado no es válido o no existe.',

                'nombreRequisito.required' => 'Por favor, ingresa el <b>Nombre del Requisito</b>.',
                'nombreRequisito.max' => 'El <b>Nombre del Requisito</b> no debe exceder los 255 caracteres.',

                // La validación unique ya NO necesita el ID para exclusión
                'nombreRequisitoCorto.unique' => 'El <b>Nombre Corto</b> ya está siendo utilizado por otro requisito.',
                'nombreRequisitoCorto.max' => 'El <b>Nombre del Requisito</b> no debe exceder los 50 caracteres.',
                'nombreRequisitoCorto.regex' => 'El <b>Nombre Corto</b> solo debe contener letras mayúsculas, números y guiones bajos (A-Z, 0-9, _).',
                'nombreRequisitoCorto.required' => 'El campo <b> Nombre Corto </b> es obligatorio.',
            ];

            $validatedData = $request->validate([
                'nombreRequisito' => 'required|string|max:255',
                // Quitar el ID del unique
                'nombreRequisitoCorto' => 'required|string|max:50|unique:requisitos_documentacion,nombre_corto|regex:/^[A-Z0-9_]+$/',
                'idTramite' => 'required|integer|exists:catalogo_tramites,id',
                'requisitoObligatorio' => 'nullable|boolean',
                'requisitoActivo' => 'nullable|boolean',
            ], $messages);


            // --- PASO 2: Preparación de Datos ---

            $datosRequisitos = [
                'nombre' => trim(mb_strtoupper($validatedData['nombreRequisito'])),
                'nombre_corto' => trim(mb_strtoupper($validatedData['nombreRequisitoCorto']))
            ];

            // --- PASO 4: Transacción y Creación ---

            DB::beginTransaction();

            // 1. CREAR el nuevo requisito en la tabla `requisitos_documentacion`
            $nuevoRequisito = RequisitoDocumentacion::create($datosRequisitos);

            // 2. Asociar el nuevo requisito al trámite usando `attach` (para Many-to-Many)
            $nuevoRequisito->tramites()->attach(
                $validatedData['idTramite'], // ID del trámite a asociar
                [
                    'obligatorio' => $validatedData['requisitoObligatorio'] ?? false,
                    'activo' => $validatedData['requisitoActivo'] ?? false,
                ]
            );

            DB::commit();

            $filtros = $this->getFilteredParameters($request);

            return redirect()
                ->route('requisitos', $filtros)
                ->with('success', "Requisito creado exitosamente.");
        } catch (ValidationException $e) {
            // Manejo específico de ERRORES DE VALIDACIÓN de Laravel
            return back()->withErrors($e->errors())->withInput(); // Añadir withInput() para persistir datos
        } catch (\Exception $e) {
            // Manejo de cualquier otro error (ej. fallo de MySQL, error de conexión, etc.)
            DB::rollBack();
            Log::error("Fallo al crear requisito: " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Ocurrió un error inesperado al crear el requisito. Inténtalo de nuevo.');
        }
    }

    /**
     * STORE: Almacena un nuevo requisito. (POST /requisitos)
     */
    public function store(Request $request)
    {
        // --- PASO 1: Validación y Mensajes ---
        try {
            $messages = [
                'nombreRequisito.required' => 'Por favor, ingresa el <b>Nombre del Requisito</b>.',
                'nombreRequisito.max' => 'El <b>Nombre del Requisito</b> no debe exceder los 255 caracteres.',

                // La validación unique ya NO necesita el ID para exclusión
                'nombreRequisitoCorto.unique' => 'El <b>Nombre Corto</b> ya está siendo utilizado por otro requisito.',
                'nombreRequisitoCorto.max' => 'El <b>Nombre del Requisito</b> no debe exceder los 50 caracteres.',
                'nombreRequisitoCorto.regex' => 'El <b>Nombre Corto</b> solo debe contener letras mayúsculas, números y guiones bajos (A-Z, 0-9, _).',
                'nombreRequisitoCorto.required' => 'El campo <b> Nombre Corto </b> es obligatorio.',
            ];

            $validatedData = $request->validate([
                'nombreRequisito' => 'required|string|max:255',
                'nombreRequisitoCorto' => 'required|string|max:50|unique:requisitos_documentacion,nombre_corto|regex:/^[A-Z0-9_]+$/',
                'requisitoActivo' => 'nullable|boolean',
            ], $messages);


            $datosRequisitos = [
                'nombre' => trim(mb_strtoupper($validatedData['nombreRequisito'])),
                'nombre_corto' => trim(mb_strtoupper($validatedData['nombreRequisitoCorto'])),
                'activo' => trim(mb_strtoupper($validatedData['requisitoActivo'])),
            ];

            // --- PASO 4: Transacción y Creación ---

            DB::beginTransaction();

            // 1. CREAR el nuevo requisito en la tabla `requisitos_documentacion`
            $nuevoRequisito = RequisitoDocumentacion::create($datosRequisitos);

            DB::commit();

            $filtros = $this->getFilteredParameters($request);

            return redirect()
                ->route('requisitos', $filtros)
                ->with('success', "Requisito creado exitosamente.");
        } catch (ValidationException $e) {
            // Manejo específico de ERRORES DE VALIDACIÓN de Laravel
            return back()->withErrors($e->errors())->withInput(); // Añadir withInput() para persistir datos
        } catch (\Exception $e) {
            // Manejo de cualquier otro error (ej. fallo de MySQL, error de conexión, etc.)
            DB::rollBack();
            Log::error("Fallo al crear requisito: " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Ocurrió un error inesperado al crear el requisito. Inténtalo de nuevo.');
        }
    }

    /**
     * EDIT: Muestra el formulario para editar un recurso. (GET /requisitos/{requisito}/edit)
     */
    public function edit(RequisitoDocumentacion $requisito)
    {
        // Pasa el objeto del requisito a la vista de edición
        return Inertia::render('Requisitos/Edit', [
            'requisito' => $requisito,
        ]);
    }

    // public function store_assignment_requisito(Request $request, $idRequisito)
    // {
    //     $idTramite = $request->input('idTramite');
    //     $idTramiteOriginal = $request->input('idTramiteOriginal');
    //     $idRequisitoOriginal = $request->input('idRequisitoOriginal');

    //     try {
    //         $messages = [
    //             'idTramite.required' => 'Debes seleccionar el <b> Trámite </b> al que se asocia este requisito.',
    //             'idTramite.integer' => 'Por favor, seleccionar un <b> Trámite </b>.', 
    //             'idTramite.exists' => 'El <b> Trámite </b> seleccionado no es válido o no existe.',

    //             'idRequisitoEditar.required' => 'Debes seleccionar el <b> Requisito </b> al que se asocia este trámite.',
    //             'idRequisitoEditar.integer' => 'Por favor, seleccionar un <b> Requisito </b>.', 
    //             'idRequisitoEditar.exists' => 'El <b> Requisito </b> seleccionado no es válido o no existe.',
    //         ];

    //         $validatedData = $request->validate([
    //             'idTramite' => 'required|integer|exists:catalogo_tramites,id',
    //             'idRequisitoEditar' => [
    //                 'required',
    //                 'integer',
    //                 'exists:requisitos_documentacion,id',      
    //             ],
    //             'requisitoObligatorio' => 'required',
    //             'requisitoActivo' => 'required',
    //         ], $messages);

    //         $requisito = RequisitoDocumentacion::find($idRequisito);

    //         if (!$requisito) {
    //             return redirect()
    //             ->route('requisitos')
    //             ->with('error', 'Requisito no encontrado en la Base de Datos.');
    //         }

    //         DB::transaction(function () use ($requisito, $idRequisito, $idTramite, $idRequisitoOriginal, $idTramiteOriginal, $validatedData) 
    //         {

    //             // 1. Verificamos si el trámite nuevo ($idTramite) ya está asociado a este requisito
    //             $existeRelacion = $requisito->tramites()->wherePivot('id_tramite_catalogo', $idTramite)->exists();

    //             if ($existeRelacion) 
    //             {
    //                 $datosPivote = [
    //                     'id_requisito' => $requisito->id,
    //                     'obligatorio' => $validatedData['requisitoObligatorio'],
    //                     'activo'      => $validatedData['requisitoActivo'],
    //                 ];

    //                 $requisito->tramites()->detach($idTramiteOriginal);
    //                 $requisito->tramites()->attach($idTramite, $datosPivote);
    //             }
    //             else 
    //             {
    //                 $idRequisitoNuevo = $idRequisito;

    //                 $datosPivote = [
    //                     'obligatorio' => $validatedData['requisitoObligatorio'],
    //                     'activo'      => $validatedData['requisitoActivo'],
    //                 ];

    //                 DB::beginTransaction();
    //                 try {
    //                     $tramite = CatalogoTramite::findOrFail($idTramite);

    //                     $tramite->requisitos()->detach($idRequisitoOriginal);
    //                     $tramite->requisitos()->syncWithoutDetaching([
    //                         $idRequisitoNuevo => $datosPivote
    //                     ]);

    //                     DB::commit();
    //                 } catch (\Exception $e) {
    //                     DB::rollBack();
    //                     return response()->json(['error' => $e->getMessage()], 500);
    //                 }
    //             }
    //         });

    //         $filtros = $this->getFilteredParameters($request);

    //         $page = $request->input('page', 1); 

    //         $props = $this->prepararVistaRequisitos($filtros);

    //         $lastPage = $props['requisitos']->lastPage();

    //         if ($lastPage < $page) {
    //             $filtros['page'] = $lastPage;
    //         }

    //         return redirect()
    //             ->route('requisitos', $filtros)
    //             ->with('success', "Asignación actualizada exitosamente.");

    //     } catch (ValidationException $e) {
    //         $filtros = $this->getFilteredParameters($request);

    //         return redirect()
    //             ->route('requisitos', $filtros) // Redirige a la ruta específica con los filtros
    //             ->withErrors($e->errors())      // Adjunta los errores (Respuesta 422 para Inertia)
    //             ->withInput();
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         $filtros = $this->getFilteredParameters($request);
    //         Log::error("Fallo al crear requisito: " . $e->getMessage(), ['exception' => $e]);
    //         return redirect()
    //             ->route('requisitos', $filtros) // Redirige a la ruta específica con los filtros
    //             ->with('error', 'Ocurrió un error inesperado al crear el requisito. Por favor, avisa al administrador del sistema.');
    //     }
    //     catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         DB::rollBack();
    //         $filtros = $this->getFilteredParameters($request);
    //         Log::warning("Intento de actualización fallido: Recurso no encontrado (ID $idRequisito o relación).");
    //         return redirect()
    //             ->route('requisitos', $filtros)
    //             ->with('error', 'El requisito o la relación de trámite asignado no fue encontrado.');
    //     } 
    // }

    public function store_assignment_requisito(Request $request, $idRequisito)
    {
        $idTramite = $request->input('idTramite');
        $idTramiteOriginal = $request->input('idTramiteOriginal');
        $idRequisitoOriginal = $request->input('idRequisitoOriginal');

        try {
            $messages = [
                'idTramite.required' => 'Debes seleccionar el <b> Trámite </b> al que se asocia este requisito.',
                'idTramite.integer' => 'Por favor, seleccionar un <b> Trámite </b>.',
                'idTramite.exists' => 'El <b> Trámite </b> seleccionado no es válido o no existe.',

                'idRequisitoEditar.required' => 'Debes seleccionar el <b> Requisito </b> al que se asocia este trámite.',
                'idRequisitoEditar.integer' => 'Por favor, seleccionar un <b> Requisito </b>.',
                'idRequisitoEditar.exists' => 'El <b> Requisito </b> seleccionado no es válido o no existe.',
            ];

            $validatedData = $request->validate([
                'idTramite' => 'required|integer|exists:catalogo_tramites,id',
                'idRequisitoEditar' => [
                    'required',
                    'integer',
                    'exists:requisitos_documentacion,id',
                ],
                'requisitoObligatorio' => 'required',
                'requisitoActivo' => 'required',
            ], $messages);

            $requisito = RequisitoDocumentacion::find($idRequisito);

            if (!$requisito) {
                return redirect()
                    ->route('requisitos')
                    ->with('error', 'Requisito no encontrado en la Base de Datos.');
            }

            if (($idRequisito != $idRequisitoOriginal) || ($idTramite != $idTramiteOriginal)) {
                $yaExisteRelacion = DB::table('catalogo_tramites_requisitos') // Asegúrate que este sea el nombre de tu tabla pivote
                    ->where('id_requisito', $idRequisito)
                    ->where('id_tramite_catalogo', $idTramite)
                    ->exists();

                if ($yaExisteRelacion) {
                    $filtros = $this->getFilteredParameters($request);
                    return redirect()
                        ->route('requisitos', $filtros)
                        ->with('error', 'No se pudo actualizar: Este trámite ya tiene asignado el requisito seleccionado.');
                }
            }

            DB::transaction(function () use ($requisito, $idRequisito, $idTramite, $idRequisitoOriginal, $idTramiteOriginal, $validatedData) {
                // 1. Verificamos si el trámite nuevo ($idTramite) ya está asociado a este requisito
                $existeRelacion = $requisito->tramites()->wherePivot('id_tramite_catalogo', $idTramite)->exists();

                if ($existeRelacion) {
                    $datosPivote = [
                        'id_requisito' => $requisito->id,
                        'obligatorio' => $validatedData['requisitoObligatorio'],
                        'activo'      => $validatedData['requisitoActivo'],
                    ];

                    $requisito->tramites()->detach($idTramiteOriginal);
                    $requisito->tramites()->attach($idTramite, $datosPivote);
                } else {
                    $idRequisitoNuevo = $idRequisito;

                    $datosPivote = [
                        'obligatorio' => $validatedData['requisitoObligatorio'],
                        'activo'      => $validatedData['requisitoActivo'],
                    ];

                    DB::beginTransaction();
                    try {
                        $tramite = CatalogoTramite::findOrFail($idTramite);

                        $tramite->requisitos()->detach($idRequisitoOriginal);
                        $tramite->requisitos()->syncWithoutDetaching([
                            $idRequisitoNuevo => $datosPivote
                        ]);

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return response()->json(['error' => $e->getMessage()], 500);
                    }
                }
            });

            $filtros = $this->getFilteredParameters($request);
            $page = $request->input('page', 1);
            $props = $this->prepararVistaRequisitos($filtros);
            $lastPage = $props['requisitos']->lastPage();

            if ($lastPage < $page) {
                $filtros['page'] = $lastPage;
            }

            return redirect()
                ->route('requisitos', $filtros)
                ->with('success', "Asignación actualizada exitosamente.");
        } catch (ValidationException $e) {
            $filtros = $this->getFilteredParameters($request);
            return redirect()
                ->route('requisitos', $filtros)
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            $filtros = $this->getFilteredParameters($request);
            Log::error("Fallo al crear requisito: " . $e->getMessage(), ['exception' => $e]);
            return redirect()
                ->route('requisitos', $filtros)
                ->with('error', 'Ocurrió un error inesperado al crear el requisito.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            $filtros = $this->getFilteredParameters($request);
            Log::warning("Intento de actualización fallido: Recurso no encontrado.");
            return redirect()
                ->route('requisitos', $filtros)
                ->with('error', 'El requisito o la relación de trámite asignado no fue encontrado.');
        }
    }

    public function update_assignment_requisitos_tramite(Request $request, $idTramite)
    {
        $requisitosString = $request->input('requisitosAsignacionesTramite');
        $requisitosIds = json_decode($requisitosString, true);

        try {
            $tramite = CatalogoTramite::find($idTramite);

            if (!$tramite) {
                return redirect()
                    ->route('requisitos')
                    ->with('error', 'Trámite no encontrado en la Base de Datos.');
            }

            DB::transaction(function () use ($tramite, $requisitosIds) {
                $requisitosIdsCollection = collect($requisitosIds);

                $requisitosActuales = $tramite->requisitos()->get();

                $requisitosSeleccionados = $tramite->requisitos()
                    ->wherePivotIn('id_requisito', $requisitosIds)
                    ->get();

                $requisitosDesseleccionados = $requisitosActuales->diff($requisitosSeleccionados);

                $idsRequisitosExistentes = $requisitosSeleccionados->pluck('id')->values();

                $idsRequisitosFaltantes = $requisitosIdsCollection->diff($idsRequisitosExistentes);

                $requisitosFaltantes = RequisitoDocumentacion::whereIn('id', $idsRequisitosFaltantes)
                    ->get();


                $datosPivote = [
                    'id_tramite_catalogo' => $tramite->id,
                    'obligatorio' => 1,
                    'activo'      => 1,
                    'editable'    => 1,
                ];

                foreach ($requisitosFaltantes as $requisito) {
                    $tramite->requisitos()->attach($requisito->id, $datosPivote);
                }

                foreach ($requisitosSeleccionados as $requisito) {
                    if ($requisito->pivot->activo == 0) {
                        $requisito->pivot->activo = 1;
                        $requisito->pivot->save();
                    }
                }

                foreach ($requisitosDesseleccionados as $requisito) {
                    if ($requisito->pivot->activo == 1) {
                        if ($requisito->pivot->editable == 1) {
                            $requisito->pivot->delete();
                        } else {
                            $requisito->pivot->activo = 0;
                            $requisito->pivot->save();
                        }
                    }
                }
            });

            $filtros = $this->getFilteredParameters($request);

            $page = $request->input('page', 1);

            $props = $this->prepararVistaRequisitos($filtros);

            $lastPage = $props['requisitos']->lastPage();

            if ($lastPage < $page) {
                $filtros['page'] = $lastPage;
            }

            return redirect()
                ->route('requisitos', $filtros)
                ->with('success', "Asignación realizada exitosamente.");
        } catch (\Exception $e) {
        }
    }

    public function update_assignment(Request $request, $idRequisito)
    {
        $tramitesString = $request->input('requisitoAsignacionesTramites');
        $tramitesIds = json_decode($tramitesString, true);

        try {
            $requisito = RequisitoDocumentacion::find($idRequisito);

            if (!$requisito) {
                return redirect()
                    ->route('requisitos')
                    ->with('error', 'Requisito no encontrado en la Base de Datos.');
            }

            DB::transaction(function () use ($requisito, $tramitesIds) {
                $tramitesIdsCollection = collect($tramitesIds);

                $tramitesActuales = $requisito->tramites()->get();

                $tramitesSeleccionados = $requisito->tramites()
                    ->wherePivotIn('id_tramite_catalogo', $tramitesIds)
                    ->get();

                $tramitesDesseleccionados = $tramitesActuales->diff($tramitesSeleccionados);

                $idsTramitesExistentes = $tramitesSeleccionados->pluck('id')->values();

                $idsTramitesFaltantes = $tramitesIdsCollection->diff($idsTramitesExistentes);

                $tramitesFaltantes = CatalogoTramite::whereIn('id', $idsTramitesFaltantes)
                    ->get();

                $datosPivote = [
                    'id_requisito' => $requisito->id,
                    'obligatorio' => 1,
                    'activo'      => 1,
                    'editable'    => 1,
                ];

                foreach ($tramitesFaltantes as $tramite) {
                    $requisito->tramites()->attach($tramite->id, $datosPivote);
                }

                foreach ($tramitesSeleccionados as $tramite) {
                    if ($tramite->pivot->activo == 0) {
                        $tramite->pivot->activo = 1;
                        $tramite->pivot->save();
                    }
                }

                foreach ($tramitesDesseleccionados as $tramite) {
                    if ($tramite->pivot->activo == 1) {
                        if ($tramite->pivot->editable == 1) {
                            $tramite->pivot->delete();
                        } else {
                            $tramite->pivot->activo = 0;
                            $tramite->pivot->save();
                        }
                    }
                }
            });

            $filtros = $this->getFilteredParameters($request);

            $page = $request->input('page', 1);

            $props = $this->prepararVistaRequisitos($filtros);

            $lastPage = $props['requisitos']->lastPage();

            if ($lastPage < $page) {
                $filtros['page'] = $lastPage;
            }

            return redirect()
                ->route('requisitos', $filtros)
                ->with('success', "Requisito actualizado exitosamente.");
        } catch (ValidationException $e) {
            // Manejo específico de ERRORES DE VALIDACIÓN de Laravel
            // Inertia/Vue lo maneja, regresando los errores al formulario.
            return back()->withErrors($e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Manejo de errores si RequisitoDocumentacion::findOrFail() o firstOrFail() falla
            DB::rollBack(); // Es buena práctica hacer rollback, aunque findOrFail no abre la transacción
            Log::warning("Intento de actualización fallido: Recurso no encontrado (ID $idRequisito o relación).");
            // Redirige al índice o back con un error
            return redirect()
                ->route('requisitos')
                ->with('error', 'El requisito o la relación de trámite asignado no fue encontrado.');
        } catch (\Exception $e) {
            // Manejo de cualquier otro error (ej. fallo de MySQL, error de conexión, etc.)
            DB::rollBack();
            $errorMessage = $e->getMessage();
            return back()->with('error', 'Ocurrió un error inesperado al actualizar el requisito. Inténtalo de nuevo. ' . $errorMessage);
        }
    }

    public function actualizaActivoCatalogoTramitesRequisitos($idRequisito, $valor)
    {
        DB::table('catalogo_tramites_requisitos')
            // Condición 1: El registro en la pivote debe pertenecer a uno de los trámites seleccionados.
            ->where('id_requisito', $idRequisito)
            // Paso 3: Realizar la actualización.
            ->update([
                'activo' => $valor,
            ]);
    }

    public function update(Request $request, $idRequisito)
    {
        try {
            $messages = [
                'nombreRequisito.required' => 'Por favor, ingresa el <b>Nombre del Requisito</b>.',
                'nombreRequisito.max' => 'El <b>Nombre del Requisito</b> no debe exceder los 255 caracteres.',
                'nombreRequisitoCorto.unique' => 'El <b>Nombre Corto</b> ya está siendo utilizado por otro requisito.',
                'nombreRequisitoCorto.max' => 'El <b>Nombre del Requisito</b> no debe exceder los 50 caracteres.',
                'nombreRequisitoCorto.regex' => 'El <b>Nombre Corto</b> solo debe contener letras mayúsculas, números y guiones bajos (A-Z, 0-9, _).',
                'nombreRequisitoCorto.required' => 'El campo <b>Nombre Corto</b> es obligatorio.',
            ];

            $validatedData = $request->validate([
                'nombreRequisito' => 'required|string|max:255',
                'nombreRequisitoCorto' => [
                    'required',
                    'string',
                    'max:50',
                    'unique:requisitos_documentacion,nombre_corto,' . $idRequisito . ',id',
                    'regex:/^[A-Z0-9_]+$/'
                ],
                'requisitoActivo' => 'nullable|boolean',
            ], $messages);

            $datosRequisitos = [
                'nombre' => trim(mb_strtoupper($validatedData['nombreRequisito'])),
                'nombre_corto' => trim(mb_strtoupper($validatedData['nombreRequisitoCorto'])),
                'activo' => $validatedData['requisitoActivo']
            ];

            $requisito = RequisitoDocumentacion::findOrFail($idRequisito);

            DB::beginTransaction();

            $requisito->update($datosRequisitos);

            $this->actualizaActivoCatalogoTramitesRequisitos($idRequisito, $validatedData['requisitoActivo']);

            DB::commit();

            $filtros = $this->getFilteredParameters($request);

            $page = $request->input('page', 1);

            $props = $this->prepararVistaRequisitos($filtros);

            $lastPage = $props['requisitos']->lastPage();

            if ($lastPage < $page) {
                $filtros['page'] = $lastPage;
            }

            return redirect()
                ->route('requisitos', $filtros)
                ->with('success', "Requisito actualizado exitosamente.");
        } catch (ValidationException $e) {
            // Manejo específico de ERRORES DE VALIDACIÓN de Laravel
            // Inertia/Vue lo maneja, regresando los errores al formulario.
            return back()->withErrors($e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Manejo de errores si RequisitoDocumentacion::findOrFail() o firstOrFail() falla
            DB::rollBack(); // Es buena práctica hacer rollback, aunque findOrFail no abre la transacción
            Log::warning("Intento de actualización fallido: Recurso no encontrado (ID $idRequisito o relación).");
            // Redirige al índice o back con un error
            return redirect()
                ->route('requisitos')
                ->with('error', 'El requisito o la relación de trámite asignado no fue encontrado.');
        } catch (\Exception $e) {
            // Manejo de cualquier otro error (ej. fallo de MySQL, error de conexión, etc.)
            DB::rollBack();
            $errorMessage = $e->getMessage();
            return back()->with('error', 'Ocurrió un error inesperado al actualizar el requisito. Inténtalo de nuevo. ' . $errorMessage);
        }
    }

    /**
     * DELETE: Elimina un requisito. (DELETE /requisitos/{requisito})
     */
    public function destroy(RequisitoDocumentacion $requisito)
    {
        // 1. Prevención: No permitir eliminar si no es editable
        if (!$requisito->editable) {
            return back()->with('error', 'Este requisito no puede ser eliminado por la configuración del sistema.');
        }

        try {
            DB::beginTransaction();

            // 2. Eliminación (MySQL se encarga de las restricciones de clave foránea)
            $requisito->delete();

            DB::commit();

            // 3. Respuesta Inertia: Redirección
            return redirect()->route('requisitos.index')
                ->with('success', "Requisito '{$requisito->nombre}' eliminado correctamente.");
        } catch (QueryException $e) {
            DB::rollBack();
            // Error de clave foránea (MySQL) si está relacionado con trámites/solicitudes
            if ($e->getCode() === '23000') { // 23000 es el código SQLSTATE para Integrity Constraint Violation
                return back()->with('error', 'No se puede eliminar este requisito porque está asignado a uno o más trámites o solicitudes.');
            }
            Log::error("Error al eliminar requisito: " . $e->getMessage());
            return back()->with('error', 'Error al intentar eliminar el requisito. Intenta más tarde.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error inesperado al eliminar el requisito.');
        }
    }
}
