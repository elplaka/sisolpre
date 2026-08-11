<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Localidad;
use App\Http\Requests\LocalidadIndexRequest;

class LocalidadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id' => [
                'required',
                'numeric',
                'digits_between:1,3',
                function ($attribute, $value, $fail) {
                    $idNumerico = intval($value);
                    $idFormateado = abs($idNumerico);
                    if (Localidad::where('id', $idFormateado)->exists()) {
                        $fail('La CLAVE de LOCALIDAD ya existe. Intenta con otra clave.');
                    }
                },
            ],
            'nombre' => 'required|string|max:50',
        ], [
            'id.required' => 'El campo clave es obligatorio.',
            'id.numeric' => 'La clave debe ser numérica.',
            'id.digits_between' => 'La clave debe tener entre 1 y 3 dígitos.',
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto válida.',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
        ]);

        try {
            DB::beginTransaction(); // Inicia la transacción

            // Crear la colonia
            $localidad = Localidad::create([
                'id' => abs($request->id),
                'nombre' => trim(mb_strtoupper($request->nombre)),
            ]);

            DB::commit(); // Confirma la transacción si todo salió bien

            return back()->with('success', 'Localidad creada exitosamente')
                ->with('localidades', Localidad::all())
                ->with('idLocalidad', $localidad->id);
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte la transacción si ocurre un error
            return response()->json(['error' => 'Hubo un error al crear la localidad: ' . $e->getMessage()], 500);
        }
    }

    public function index(LocalidadIndexRequest $request)
    {
        $localidades = Localidad::search($request->q)
            ->orderBy('nombre')
            ->limit($request->get('limit', 20))
            ->get(['id', 'nombre']);

        return response()->json([
            'data' => $localidades,
            'meta' => [
                'total' => $localidades->count(),
                'limit' => (int)$request->get('limit', 20)
            ]
        ]);
    }

    public function show($id)
    {
        $localidad = Localidad::find($id);

        if (!$localidad) {
            return response()->json([
                'message' => 'Localidad no encontrada',
                'errors' => ['id' => ['La localidad con ID ' . $id . ' no existe']]
            ], 404);
        }

        return response()->json([
            'data' => $localidad
        ]);
    }
}
