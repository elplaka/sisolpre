<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof AuthorizationException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }
            return redirect('/')->with('error', 'No tienes permiso para acceder a esta página.');
        }

        if ($exception instanceof TokenMismatchException) {
            // Si la petición viene de Inertia, el 'back()' refresca los props y el token CSRF
            // Enviamos un mensaje flash para que Vue pueda mostrar una notificación
            return back()->with([
                'error' => 'Tu sesión ha expirado por inactividad. Por favor, intenta enviar el formulario de nuevo.'
            ]);
        }

        return parent::render($request, $exception);
    }
}
