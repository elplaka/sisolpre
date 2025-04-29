<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Colonia;

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
        
            return back()->with('success', 'Colonia creada exitosamente')
                         ->with('colonias', Colonia::all())
                         ->with('idColonia', $colonia->id);
        } catch (\Exception $e) {
            DB::rollBack(); // Revierte la transacción si ocurre un error
            return response()->json(['error' => 'Hubo un error al crear la colonia: ' . $e->getMessage()], 500);
        }
    }
}
