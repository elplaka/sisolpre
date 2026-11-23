<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Restringido</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center h-screen">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-8 text-center border border-gray-200/80 dark:border-gray-700">

        <!-- Icono de Candado / Alerta con acento de color -->
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 mb-5 border border-red-200/50 dark:border-red-800/50">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>

        <!-- Título -->
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">Acceso Restringido</h1>

        <!-- Mensaje descriptivo -->
        <p class="text-gray-600 dark:text-gray-400 text-sm mb-6 leading-relaxed">
            Lo sentimos, tu usuario no cuenta con los permisos necesarios para acceder al módulo solicitado.
        </p>

        <a href="{{ url('/admin/dashboard') }}" class="inline-block w-full bg-red-800 hover:bg-red-900 text-white font-semibold py-2.5 px-4 rounded-full shadow-sm hover:shadow transition-all duration-200">
            Volver al Panel Principal
        </a>
    </div>
</body>

</html>