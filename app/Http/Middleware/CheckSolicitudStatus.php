<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Solicitud;

class CheckSolicitudStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->route('id'); // obtiene el parámetro id de la ruta
        $solicitud = Solicitud::find($id);

        if (!$solicitud) {
            abort(404, 'Solicitud no encontrada.');
        }

        // Validar que el estatus sea 99 para permitir acceso
        if ($solicitud->id_estatus == 99) 
        {
            return $next($request);
        } 
        else 
        {
            return redirect()->route('solicitudes.print-preview-pdf', ['id' => $id]);
        }

        return $next($request);
    }
}
