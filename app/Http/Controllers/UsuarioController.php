<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{

    public function index(Request $request)
    {
        $selectedTypes = $request->input('selectedTypes', []);
        $isActive = filter_var($request->input('isActive'), FILTER_VALIDATE_BOOLEAN);
        $isInactive = filter_var($request->input('isInactive'), FILTER_VALIDATE_BOOLEAN);

        $sortColumn = $request->input('sortColumn', 'id'); // 'id' como columna por defecto
        $sortDirection = $request->input('sortDirection', 'asc'); // 'asc' como dirección por defecto

        $searchQuery = $request->input('query');
        if ($searchQuery) {
            $usersQuery = User::join('user_types', 'users.user_type_id', '=', 'user_types.id')
                ->with('userType')->select('users.*') // Incluye la relación primero
                ->where(function ($query) use ($searchQuery) {
                    $query->where('users.name', 'like', '%' . $searchQuery . '%')
                        ->orWhere('last_name', 'like', '%' . $searchQuery . '%');
                });

            if ($sortColumn == 'user_types.name') {
                $usersQuery->orderBy('user_types.name', $sortDirection)->orderBy('users.name', $sortDirection);
            } else {
                $usersQuery->orderBy($sortColumn, $sortDirection);
            }

            // Obtener los distintos user_type_id de la colección 
            $userTypeIds = $usersQuery->pluck('user_type_id')->unique();
            foreach ($userTypeIds as $userTypeId) {
                if (!in_array($userTypeId, $selectedTypes)) {
                    $selectedTypes[] = $userTypeId;
                }
            }

            if (!empty($selectedTypes)) {
                $usersQuery->whereIn('user_type_id', $selectedTypes);
            }

            $usersQuery->where(function ($query) use ($isActive, $isInactive) {
                if ($isActive) {
                    $query->orWhere('es_activo', true);
                }
                if ($isInactive) {
                    $query->orWhere('es_activo', false);
                }
            });

            $userIdsTypes = $usersQuery->pluck('id');

            // Obtener usuarios activos
            $activos = User::where('es_activo', 1)->count();
            // Obtener usuarios inactivos
            $inactivos = User::where('es_activo', 0)->count();

            $users = $usersQuery->paginate(10);
        } else {
            $usersQuery = User::join('user_types', 'users.user_type_id', '=', 'user_types.id')
                ->with('userType')->select('users.*');

            if ($sortColumn == 'user_types.name') {
                $usersQuery->orderBy('user_types.name', $sortDirection)->orderBy('users.name', $sortDirection);
            } else {
                $usersQuery->orderBy($sortColumn, $sortDirection);
            }

            $usersType = $usersQuery;
            $userIdsTypes = $usersType->pluck('id');

            if (empty($selectedTypes)) {
                $usersType = $usersQuery;
                $userIds = $usersType->pluck('id');

                $usersQuery->where(function ($query) use ($isActive, $isInactive) {
                    if ($isActive) {
                        $query->orWhere('es_activo', true);
                    }
                    if ($isInactive) {
                        $query->orWhere('es_activo', false);
                    }
                });
                $userIdsTypes = $usersType->pluck('id');
            } else {
                $usersQuery->whereIn('user_type_id', $selectedTypes);
                $usersType = $usersQuery;
                $userIds = $usersType->pluck('id');

                $usersQuery->where(function ($query) use ($isActive, $isInactive) {
                    if ($isActive) {
                        $query->orWhere('es_activo', true);
                    }
                    if ($isInactive) {
                        $query->orWhere('es_activo', false);
                    }
                });
                if ($isActive || $isInactive) {
                    $userIdsTypes = $usersQuery->pluck('id');
                }
            }

            // Obtener usuarios activos
            $activos = User::where('es_activo', 1)->whereIn('id', $userIds)->count();
            // Obtener usuarios inactivos
            $inactivos = User::where('es_activo', 0)->whereIn('id', $userIds)->count();

            $users = $usersQuery->paginate(10);
        }

        // Obtener los tipos de usuario con el conteo basado en los usuarios filtrados
        $types = UserType::withCount(['users as users_count' => function ($query) use ($userIdsTypes) {
            $query->whereIn('id', $userIdsTypes); // Solo contar usuarios que están en el filtro
        }])->orderBy('name')->get();


        return Inertia::render('Users/Index', [
            'users' => $users,
            'types' => $types,
            'userAuth' => Auth::user(),
            'searchQuery' => $searchQuery,  // Aquí pasamos solo el valor de searchQuery
            'selectedTypes' => $selectedTypes,
            'isActive' => $isActive,
            'isInactive' => $isInactive,
            'activos' => $activos,
            'inactivos' => $inactivos,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'nickname' => 'required|string|max:50|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'celular' => 'required|digits:10',
            'password' => 'required|string|min:3|confirmed',
        ], [
            // Mensajes personalizados
            'name.required' => 'El campo NOMBRE es obligatorio.',
            'name.string' => 'El NOMBRE debe ser una cadena de texto válida.',
            'name.max' => 'El NOMBRE no puede tener más de 255 caracteres.',
            'last_name.required' => 'El campo APELLIDOS es obligatorio.',
            'last_name.string' => 'Los APELLIDOS deben ser una cadena de texto válida.',
            'last_name.max' => 'Los APELLIDOS no pueden tener más de 255 caracteres.',
            'email.required' => 'El campo CORREO ELECTRÓNICO es obligatorio.',
            'nickname.unique' => 'El NICKNAME ya está registrado.',
            'nickname.required' => 'El campo NICKNAME es obligatorio.',
            'nickname.max' => 'El NICKNAME no puede tener más de 50 caracteres.',
            'email.email' => 'Debe proporcionar una dirección de CORREO ELECTRÓNICO válida.',
            'email.unique' => 'El CORREO ELECTRÓNICO ya está registrado.',
            'celular.required' => 'El número de CELULAR es obligatorio.',
            'celular.digits' => 'El número de CELULAR debe tener exactamente 10 dígitos.',
            'password.required' => 'La CONTRASEÑA es obligatoria.',
            'password.min' => 'La CONTRASEÑA debe tener al menos 3 caracteres.',
            'password.confirmed' => 'La confirmación de CONTRASEÑA no coincide.'
        ]);

        try {
            DB::beginTransaction(); // Inicia la transacción

            // Crear el usuario
            $user = User::create([
                'name' => trim(mb_strtoupper($request->name)),
                'last_name' => trim(mb_strtoupper($request->last_name)),
                'nickname' => trim(mb_strtolower($request->nickname)),
                'email' => trim(mb_strtolower($request->email)),
                'celular' => $request->celular,
                'password' => Hash::make($request->password),
                'user_type_id' => $request->type_id,
                'genero' => $request->genero,
                'verificado' => filter_var($request->verificado, FILTER_VALIDATE_BOOLEAN) ? 1 : 0
            ]);

            switch ($request->type_id) {
                case 1:
                    $user->assignRole('ADMINISTRADOR');
                    break;
                case 2:
                    $user->assignRole('DIRECTOR');
                    break;
                case 3:
                    $user->assignRole('AUDITOR');
                    break;
                case 4:
                    $user->assignRole('AUXILIAR');
                    break;
                default:
                    // Opcional: puedes lanzar un error si no es válido
                    throw new \Exception('Tipo de usuario inválido.');
            }

            DB::commit(); // Confirma la transacción si todo salió bien

            return back()->with('success', 'Usuario creado exitosamente')->with('users', User::all());
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte la transacción si ocurre un error
            return response()->json(['error' => 'Hubo un error al crear el usuario: ' . $e->getMessage()], 500);
        }
    }

    public function search(Request $request)
    {
        $selectedTypes = $request->input('selectedTypes', []);
        $isActive = filter_var($request->input('isActive'), FILTER_VALIDATE_BOOLEAN);
        $isInactive = filter_var($request->input('isInactive'), FILTER_VALIDATE_BOOLEAN);

        $sortColumn = $request->input('sortColumn', 'id'); // 'id' como columna por defecto
        $sortDirection = $request->input('sortDirection', 'asc'); // 'asc' como dirección por defecto

        $searchQuery = $request->input('query');  // Recibe el valor de query

        if ($searchQuery) {
            $usersQuery = User::join('user_types', 'users.user_type_id', '=', 'user_types.id')
                ->with('userType')->select('users.*') // Incluye la relación primero
                ->where(function ($query) use ($searchQuery) {
                    $query->where('users.name', 'like', '%' . $searchQuery . '%')
                        ->orWhere('last_name', 'like', '%' . $searchQuery . '%');
                });

            if ($sortColumn == 'user_types.name') {
                $usersQuery->orderBy('user_types.name', $sortDirection)->orderBy('users.name', $sortDirection);
            } else {
                $usersQuery->orderBy($sortColumn, $sortDirection);
            }

            if (!empty($selectedTypes)) {
                $usersQuery->whereIn('user_type_id', $selectedTypes);
            }

            $usersQuery->where(function ($query) use ($isActive, $isInactive) {
                if ($isActive) {
                    $query->orWhere('es_activo', true);
                }
                if ($isInactive) {
                    $query->orWhere('es_activo', false);
                }
            });

            $userIdsTypes = $usersQuery->pluck('id');

            // Obtener usuarios activos
            $activos = User::where('es_activo', 1)->whereIn('id', $userIdsTypes)->count();
            // Obtener usuarios inactivos
            $inactivos = User::where('es_activo', 0)->whereIn('id', $userIdsTypes)->count();

            // Obtener los distintos user_type_id de la colección 
            $userTypeIds = $usersQuery->pluck('user_type_id')->unique();
            $selectedTypes = [];
            foreach ($userTypeIds as $userTypeId) {
                if (!in_array($userTypeId, $selectedTypes)) {
                    $selectedTypes[] = strval($userTypeId);
                }
            }
        } else {
            $userIdsTypes = User::pluck('id');

            $usersQuery = User::join('user_types', 'users.user_type_id', '=', 'user_types.id')
                ->with('userType')->select('users.*');

            $userIds = $usersQuery->pluck('id');

            if ($sortColumn == 'user_types.name') {
                $usersQuery->orderBy('user_types.name', $sortDirection)->orderBy('users.name', $sortDirection);
            } else {
                $usersQuery->orderBy($sortColumn, $sortDirection);
            }

            if (!empty($selectedTypes)) {
                $usersQuery->whereIn('user_type_id', $selectedTypes);
            }

            $usersQuery->where(function ($query) use ($isActive, $isInactive) {
                if ($isActive) {
                    $query->orWhere('es_activo', true);
                }
                if ($isInactive) {
                    $query->orWhere('es_activo', false);
                }
            });
            // Obtener usuarios activos
            $activos = User::where('es_activo', 1)->whereIn('id', $userIds)->count();
            // Obtener usuarios inactivos
            $inactivos = User::where('es_activo', 0)->whereIn('id', $userIds)->count();
        }
        $users = $usersQuery->paginate(10);

        // Obtener los tipos de usuario con el conteo basado en los usuarios filtrados
        $types = UserType::withCount(['users as users_count' => function ($query) use ($userIdsTypes) {
            $query->whereIn('id', $userIdsTypes); // Solo contar usuarios que están en el filtro
        }])->orderBy('name')->get();

        // Obtener usuarios activos
        $activos = User::where('es_activo', 1)->whereIn('id', $userIdsTypes)->count();
        // Obtener usuarios inactivos
        $inactivos = User::where('es_activo', 0)->whereIn('id', $userIdsTypes)->count();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'types' => $types,
            'searchQuery' => $searchQuery,  // Aquí pasamos solo el valor de searchQuery
            'userAuth' => Auth::user(),
            'selectedTypes' => $selectedTypes,
            'isActive' => $isActive,
            'isInactive' => $isInactive,
            'activos' => $activos,
            'inactivos' => $inactivos,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->name = trim(mb_strtoupper($request->name));
        $user->last_name = trim(mb_strtoupper($request->last_name));
        $user->email = trim(mb_strtolower($request->email));
        $user->celular = $request->celular;
        $user->genero = $request->genero;
        $user->verificado = filter_var($request->verificado, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $user->user_type_id = $request->type_id;
        $user->isAdmin = boolval($user->user_type_id == 1);
        $user->es_activo = $request->es_activo;

        if (strlen($request->password)) {
            $user->password = Hash::make($request->password);
        }

        switch ($request->type_id) {
            case 1:
                $user->syncRoles(['ADMINISTRADOR']);     // Reemplaza el rol anterior
                break;
            case 2:
                $user->syncRoles(['DIRECTOR']);     // Reemplaza el rol anterior
                break;
            case 3:
                $user->syncRoles(['AUDITOR']);     // Reemplaza el rol anterior
                break;
            case 4:
                $user->syncRoles(['AUXILIAR']);     // Reemplaza el rol anterior
                break;
            default:
                // Opcional: puedes lanzar un error si no es válido
                throw new \Exception('Tipo de usuario inválido.');
        }

        $user->syncPermissions([]);          // Limpia permisos directos (opcional, si usas roles correctamente)

        //HAY QUE ENVIAR UN CORREO ELECTRÓNICO DE CONFIRMACIÓN CON LOS DATOS Y CON EL TOKEN

        $user->update();

        return back()->with('success', 'Usuario actualizado con éxito');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function registrar(Request $request)  //Registra usuarios POR FUERA del SISTEMA
    {
        // Validación de los datos del formulario
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'nickname' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:3|confirmed',
        ]);

        // Crear el usuario
        $user = User::create([
            'name' => $request->input('name'),
            'last_name' => $request->input('last_name'),
            'nickname' => $request->input('nickname'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'isAdmin' => 1,
            'user_type_id' => 1
        ]);

        $user->assignRole('ADMINISTRADOR');

        return redirect()->route('admin.login')->with('success', 'Usuario registrado exitosamente. ¡Bienvenido!');
    }

    public function showRegisterForm()
    {
        return Inertia::render('Auth/Register');
    }
}
