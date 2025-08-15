<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EstadisticasController extends Controller
{
    //
    public function index(Request $request)  
    {
        // Aquí puedes manejar la lógica para las estadísticas
        // Por ejemplo, podrías obtener datos de la base de datos y pasarlos a una vista

        // Retornar una vista de Inertia con los datos necesarios
        return inertia('Estadisticas/Index', [
            'userAuth' => $request->user(),
            // Puedes agregar más datos aquí si es necesario
        ]);
    }
}
