<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Colonia;
use App\Http\Requests\ColoniaIndexRequest;

class ColoniaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50',
        ], [
            // Mensajes personalizados
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto válida.',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
        ]);

        try {
            DB::beginTransaction(); // Inicia la transacción

            // Crear la colonia
            $colonia = Colonia::create([
                'nombre' => trim(mb_strtoupper($request->nombre)),
            ]);

            DB::commit(); // Confirma la transacción si todo salió bien

            return back()->with([
                'success' => 'Colonia creada exitosamente',
                'idColonia' => $colonia->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte la transacción si ocurre un error
            return response()->json(['error' => 'Hubo un error al crear la colonia: ' . $e->getMessage()], 500);
        }
    }

    public function index(ColoniaIndexRequest $request)
    {
        $colonias = Colonia::search($request->q)
            ->orderBy('nombre')
            ->limit($request->get('limit', 20))
            ->get(['id', 'nombre']);

        return response()->json([
            'data' => $colonias,
            'meta' => [
                'total' => $colonias->count(),
                'limit' => (int)$request->get('limit', 20)
            ]
        ]);
    }

    public function show($id)
    {
        $colonia = Colonia::find($id);

        if (!$colonia) {
            return response()->json([
                'message' => 'Colonia no encontrada',
                'errors' => ['id' => ['La colonia con ID ' . $id . ' no existe']]
            ], 404);
        }

        return response()->json([
            'data' => $colonia
        ]);
    }
}
