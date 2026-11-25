<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    {{-- <link rel="icon" href="/Favicon.png" type="image/png"> --}}
    <link rel="icon" href="{{ asset('Favicon.png') }}" type="image/png">



    <!-- Scripts -->
    @routes
    {{-- @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"]) --}}
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])

    @inertiaHead
</head>

<body class="font-sans antialiased">
    <!-- Spinner de carga inicial -->
    <div id="initial-loader"
        class="fixed inset-0 z-[99999] flex items-center justify-center bg-white/50">

        <div class="flex items-center">
            <div class="h-8 w-8 rounded-full border-[3px] border-gray-300/40 border-t-color1-500 animate-spin"></div>

            <span
                class="initial-loader__text"
                style="color: black; font-size: 0.875rem; padding-left: 6px; font-weight: 300; letter-spacing: 0.01em; line-height: 1.25;">
                Cargando !!!
            </span>
        </div>
    </div>
    @inertia
</body>

</html>