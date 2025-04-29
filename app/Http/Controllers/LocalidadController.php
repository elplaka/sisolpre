<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Localidad;

class LocalidadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50',
        ], [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto válida.',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
        ]); 

        try {
            DB::beginTransaction(); // Inicia la transacción

            // Crear la colonia
            $localidad = Localidad::create([
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
}